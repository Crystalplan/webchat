<?php
require('config.php');
require('func/common.php');

/**
 * websocket server
 */
class Server
{
    private $serv;
    private $redis;
    private $chat_bind_fd = 'chat:bind:fd:'; //fd对应的uid
    private $chat_bind_uid = 'chat:bind:uid:'; // uid对应的fd
    private $chat_group_fd = 'chat:group:fd:'; // group中的fd  redis:set

    private $timeout = 180;
    private $topic_wsmsg_push = 'chat:topic:wsmsg:push'; // 消息推送订阅主题

    public function __construct()
    {
        $this->serv = new swoole_websocket_server("0.0.0.0", 9501);
        $this->serv->set(
            [
                'worker_num' => 4,
                'task_worker_num' => 8,
                'daemonize' => false,
                'log_file' => '/data/logs/swoole/error/' . LOG_NAME
            ]
        );
        $this->serv->on("start", [$this, 'onStart']);
        $this->serv->on("workerstart", [$this, 'onWorkerStart']);
        $this->serv->on("open", [$this, 'onOpen']);
        $this->serv->on("message", [$this, 'onMessage']);
        $this->serv->on("task", [$this, 'onTask']);
        $this->serv->on("finish", [$this, 'onFinish']);
        $this->serv->on("close", [$this, 'onClose']);
        $this->serv->start();
    }

    /**
     * @param $serv
     */
    public function onStart($serv)
    {
        swoole_set_process_name("chat_ws_master");
    }

    /**
     * 此事件在Worker进程/Task进程启动时发生
     * @param $serv
     * @param $worker_id
     */
    public function onWorkerStart($serv, $worker_id)
    {
        if ($this->redis == null) {
            $this->redis = redis();
        }
        // 首个worker进程
        if ($worker_id === 0) {
            //初始化异步redis客户端对象
            $redis_client = new swoole_redis($options = ['password' => REDIS_PWD]);
            //redis接收--发布消息
            $redis_client->on('message', function (swoole_redis $redis_client, $result) {
                if ($result[0] == 'message') {
                    $this->serv->task(['flag' => 'redisPub', 'data' => $result]);
                }
            });
            //redis连接--订阅主题
            $redis_client->connect(REDIS_HOST, REDIS_PORT, function (swoole_redis $redis_client, $result) {
                $redis_client->subscribe($this->topic_wsmsg_push);
            });
        }
    }

    /**
     * 监听ws连接事件
     * @param $serv
     * @param $request object http请求对象 header/server/fd等
     */
    public function onOpen($serv, $request)
    {
        // todo
    }

    /**
     * 监听ws消息事件
     * @param $serv
     * @param $frame object swoole_websocket_frame对象，包含了客户端发来的数据帧信息
     */
    public function onMessage($serv, $frame)
    {
        // 数据签名校验
        $res = $this->validateSign(json_decode($frame->data, true));
        if (!$res) {
            writeLog(LOG_NAME, 'validateSign error. connect close. $fd: ' . $frame->fd);
            $serv->close($frame->fd);
        }
        //加入task任务
        $serv->task(['fd' => $frame->fd, 'data' => $frame->data, 'flag' => 'onMessage']);
    }

    /**
     * 异步任务
     * @param $serv
     * @param $task_id            string 任务ID
     * @param $src_worker_id      string Worker进程ID
     * @param $task_data          mixed 任务的内容
     */
    public function onTask($serv, $task_id, $src_worker_id, $task_data)
    {
        writeLog(LOG_NAME, '$task_data: ' . json_encode($task_data));
        $fd = isset($task_data['fd']) ? $task_data['fd'] : 0;
        $flag = $task_data['flag']; //标志动作来源
        if ($flag === 'onMessage') {
            //一、处理onMessage投递过来的任务
            $data = json_decode($task_data['data'], true);
            $msg_type = $data['type']; // 消息类型
            switch ($msg_type) {
                case  'bind':// 1、用户uid和连接fd绑定
                    $uid = $data['uid'];
                    $this->redis->set($this->chat_bind_fd . $fd, $uid, $this->timeout);
                    $this->redis->set($this->chat_bind_uid . $uid, $fd, $this->timeout);
                    writeLog(LOG_NAME, 'bind, uid=' . $uid . ',fd=' . $fd);
                    // todo
                    // 查询用户群组并绑定
                    $group = 0;
                    $this->redis->sadd($this->chat_group_fd . $group, $fd);
                    break;
                case 'heartbeat'://2、心跳维持
                    $this->setActive($serv, $fd);
                    break;
                case 'customized'://  自定义，调用内部RPC
                    if (empty($data['function'])) {
                        $serv->push($fd, exitJson(-1, '缺少必要function参数'));
                        break;
                    }
                    try {
                        $data['uid'] = $this->redis->get($this->chat_bind_fd . $fd);
                        $param = [
                            'data' => json_encode($data),
                            'rtime' => time()
                        ];
                        $param['sign'] = makeSign($param, SIGN_KEY);
                        $yar_client = new Yar_Client(INSIDE_RPC_DOMAIN . "api/rpc");
                        $res = $yar_client->customized($param);
                        writeLog(LOG_NAME, 'onMessage task customized res: ' . json_encode($res));
                    } catch (Exception $e) {
                        writeLog(LOG_NAME, 'onMessage task Exception message: ' . $e->getMessage());
                    };
                    break;
                default:
                    writeLog(LOG_NAME, '消息类型不存在');
                    $serv->close($fd);
            }
        } elseif ($flag === 'redisPub') {
            //二、处理redis主题推送消息
            $result = $task_data['data'];
            $data = json_decode($result[2], true);
            if (empty($data) || empty($data['type']) || empty($data['to']) || empty($data['content']) || empty($data['rtime'])) {
                return false;
            }
            switch ($data['type']) {
                case 'group': // 群组内广播
                    $fds = $this->redis->smembers($this->chat_group_fd . $data['to']);
                    if (!$fds) {
                        writeLog(LOG_NAME, 'Push msg error. The group cache is not exists. group id: ' . $data['to']);
                        continue;
                    }
                    foreach ($fds as $k => $v) {
                        if ($this->serv->exist($v)) { //判断fd连接是否有效
                            $this->serv->push($v, $data['content']);
                        } else {
                            $this->redis->sRem($this->chat_group_fd . $data['to'], $v);
                        }
                    }
                    break;
                case 'userid': // 对特定用户推送
                    $fd = $this->redis->get($this->chat_bind_uid . $data['to']);
                    if (!$fd) {
                        writeLog(LOG_NAME, 'Push msg error. The uid cache is not exists. $uid: ' . $data['to']);
                        continue;
                    }
                    if ($this->serv->exist($fd)) { //判断fd连接是否有效
                        $this->serv->push($fd, $data['content']);
                    }
                    break;
                default:
                    writeLog(LOG_NAME, '推送类型不存在。$push_type: ' . $data['type']);
            }
        } else {
            writeLog(LOG_NAME, 'task任务标志不存在');
            if (!empty($fd)) {
                $serv->close($fd);
            }
        }
    }

    /**
     * @param $serv
     * @param $task_id int 任务ID
     * @param $data    mixed 任务的处理结果
     */
    public function onFinish($serv, $task_id, $data)
    {
        // task进程的onTask事件中没有调用finish方法或者return结果，worker进程不会触发onFinish
    }

    /**
     * close
     * @param $serv
     * @param $fd int 连接id
     */
    public function onClose($serv, $fd)
    {
        $uid = $this->redis->get($this->chat_bind_fd . $fd);
        writeLog(LOG_NAME, '$uid: ' . ($uid ? $uid : '--, ') . '$fd: ' . $fd . ' close.');

        $this->redis->del($this->chat_bind_fd . $fd);
        $this->redis->del($this->chat_bind_uid . $uid);
        // todo
        // 查询群组,并删除cash
        $group = 0;
        $this->redis->srem($this->chat_group_fd . $group, $fd);
    }

    /**
     * 设置活跃
     * @param  $serv object
     * @param  $fd   int
     */
    private function setActive($serv, $fd)
    {
        $uid = $this->redis->get($this->chat_bind_fd . $fd);
        if (!$uid) {
            $serv->close($fd);
            return;
        }
        $this->redis->set($this->chat_bind_fd . $fd, $uid, $this->timeout);
        $this->redis->set($this->chat_bind_uid . $uid, $fd, $this->timeout);
    }

    /**
     * 校验数据签名
     * @return true/exit
     */
    private function validateSign($data)
    {
        if (!$data['rtime'] || !$data['sign']) {
            writeLog(LOG_NAME, 'validateSign error. rtime or sign is empty.');
            return false;
        }
        $make_sign = makeSign($data, SIGN_KEY);
        if ($make_sign !== $data['sign']) {
            writeLog(LOG_NAME, 'validateSign error. $make_sign: ' . $make_sign . ', $sign: ' . $data['sign']);
            return false;
        }
        return true;
    }

}

$obj = new Server();
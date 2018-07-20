<?php
/**
 * User: sky
 * Date: 2018/7/9
 * Time: 9:07
 */
/**
 * redis对象实例化
 * @return Redis
 */
function redis()
{
    try {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        if (!empty(REDIS_PWD)) {
            $redis->auth(REDIS_PWD);
        }
        if (!empty(REDIS_DB_INDEX)) {
            $redis->select(REDIS_DB_INDEX);
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    return $redis;
}

/**
 * 日志写入
 * @param $filename    string 文件名(不带路径)
 * @param $content     string 数据内容
 */
function writeLog($filename, $content)
{
    $path = '/data/logs/swoole/' . date('Ymd') . '/';
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
    file_put_contents($path . $filename, $content . ' | ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
}

/**
 * CURL
 * @param $url  string 跳转地址
 * @param $data array 参数
 * @param array array $httpheader
 * @return mixed
 */
function curl($url, $data, $httpheader = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    if ($httpheader) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/**
 * 生成数据签名
 * @param $data
 * @param $key
 * @return string
 */
function makeSign($data, $key)
{
    $str = '';
    ksort($data);
    foreach ($data as $k => $v) {
        if ('sign' !== $k && '' !== $v) {
            $str .= $k . '=' . $v . '&';
        }
    }
    $str .= 'key=' . $key;
    $sign = md5($str);
    return $sign;
}

/**
 * 生成JSON数据返回值
 */
function exitJson($errcode, $errmsg, $data = [], $extra = [])
{
    $return = ['errcode' => $errcode, 'errmsg' => $errmsg];
    if (!empty($data)) {
        $return['data'] = $data;
    }
    if (!empty($extra)) {
        foreach ($extra as $k => $v) {
            $return[$k] = $v;
        }
    }
    $json = json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    writeLog(LOG_NAME, 'L: ' . __LINE__ . ', echo: ' . $json);
    exit($json);
}
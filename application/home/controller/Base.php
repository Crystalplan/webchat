<?php
/**
 * 基础类
 * User: sky
 * Date: 2018/7/3
 * Time: 9:58
 */

namespace app\home\controller;

use think\Controller;
use think\Log;

class Base extends Controller
{
    protected $captchaKey = 'chat:home:captcha:';//验证码前缀
    protected $accessTokenKey = 'chat:home:access_token:'; //access_token前缀
    protected $accessTokenKeyExpires = 3600; //有效时间，秒
    protected $userInfoKey = 'chat:home:user:info:'; //user info前缀
    protected $userInfoKeyExpires = 86400; //有效时间，秒
    protected $loginUid = 0;

    public function __construct($loginFlag = true)
    {
        parent::__construct();
        if ($loginFlag) {
            $this->checkLogin();
        }
    }

    /**
     * 返回json数据
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
        Log::write('F: ' . __FUNCTION__ . ', echo: ' . $json);
        exit($json);
    }

    /**
     * 生成令牌
     */
    protected function makeAccessToken($uid)
    {
        $ip = getIp();
        if ($ip == 'Unknow') {
            return false;
        }
        $rand = mt_rand(100, 99999);
        $data_arr = array(
            'id' => $uid,
            'ip' => $ip,
            'iat' => time(), //签发时间戳
            'rand' => $rand
        );
        $data_arr['sign'] = makeSign($data_arr, config('token_key'));
        $accessToken = aes_encrypt(json_encode($data_arr), config('token_key'));
        $redis = redis();
        $redis->setex($this->accessTokenKey . $uid, $this->accessTokenKeyExpires, $rand);
        return $accessToken;
    }

    /**
     * 检查是否已经登录
     */
    protected function checkLogin()
    {
        isset($_SERVER['HTTP_ACCESS_TOKEN']) || $this->exitJson(5, '无效的凭证1');
        Log::write('F: ' . __FUNCTION__ . ', access_token: ' . $_SERVER['HTTP_ACCESS_TOKEN']);
        $token = aes_decrypt($_SERVER['HTTP_ACCESS_TOKEN'], config('token_key'));
        if (!$token) {
            $this->exitJson(5, '无效的凭证');
        }
        $tokenArr = json_decode($token, true);
        if (!isset($tokenArr['sign']) || $tokenArr['sign'] != makeSign($tokenArr, config('token_key'))) {
            $this->exitJson(5, '无效的凭证');
        }
        if (!isset($tokenArr['id']) || !is_numeric($tokenArr['id']) || !isset($tokenArr['ip'])) {    // $tokenArr['ip'] != getIp()
            $this->exitJson(5, '无效的凭证');
        }
        $this->loginUid = $tokenArr['id'];
        Log::write('F: ' . __FUNCTION__ . ', uid: ' . $tokenArr['id']);
        //距离签发时间超过1天，务必重新获取
        if (time() - $tokenArr['iat'] >= 86400) {
            $this->exitJson(5, '无效的凭证');
        }
        //
        $redis = redis();
        $tokenCache = $redis->get($this->accessTokenKey . $tokenArr['id']);
        if (!$tokenCache || !isset($tokenArr['rand']) || $tokenCache != $tokenArr['rand']) {
            $this->exitJson(5, '无效的凭证');
        }
        //刷新过期时间
        $redis->expire($this->accessTokenKey . $tokenArr['id'], $this->accessTokenKeyExpires);
    }

    /**
     * 获取账号信息
     * @return type
     */
    protected function getUserInfo()
    {
        if ($this->loginUid === 0) {
            $this->exitJson(5, '无效的凭证');
        }
        $redis = redis();
        $userInfo = $redis->hGetAll($this->userInfoKey . $this->loginUid);
        if (!$userInfo) {
            $this->exitJson(5, '无效的凭证');
        }
        return $userInfo;
    }

    /**
     * 检查接口签名及相关参数
     * @return true/exit json
     */
    protected function validateSign()
    {
        $passArr = [
            '/home/login/getCaptcha',
            '/home/login/logout',
            '/home/login/checkLogin'
        ];
        if (!request()->isAjax() || in_array($_SERVER['REQUEST_URI'], $passArr)) {
            return true;
        }
        // 校验
        $post = trimArray(input('post.'));
        if (!isset($post['rtime']) || !is_numeric($post['rtime'])) {
            $this->exitJson(1, '请正确输入rtime');
        }
        if (!isset($post['sign']) || !is_string($post['sign']) || strlen($post['sign']) !== 32) {
            $this->exitJson(1, '请正确输入sign');
        }
        $makeSign = makeSign($post, config('sign_key'));
        if ($makeSign !== $post['sign']) {
            $this->exitJson(6, '数据签名错误');
        }
        return true;
    }
}
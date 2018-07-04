<?php

/**
 * 登录
 * User: sky
 * Date: 2018/6/29
 * Time: 13:57
 */

namespace app\home\controller;

use \captcha\Captcha;
use app\home\model\Base as B;
use think\Log;

class Login extends Base
{
    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * 获取验证码
     */
    public function getCaptcha()
    {
        $captcha = new Captcha();
        $captcha->doimg();
        // 验证码保存到redis中
        $redis = redis();
        $key = $this->_captchaKey . getIp();
        $val = $captcha->getCode();
        $redis->setex($key, 60, $val);
    }

    /**
     * 登录页
     */
    public function index()
    {
        if (request()->isAjax()) {
            $post = trimArray(input('post.'));
            $validate = validate('Login');
            if (!$validate->scene('index')->check($post)) {
                $errmsg = $validate->getError();
                $this->exitJson(1, $errmsg);
            }
            $username = $post['username'];
            $password = aes_decrypt($post['password']);
            if (!$password) {
                $this->exitJson(1, '密码格式不正确');
            }
            $captcha = $post['captcha'];
            $redis = redis();
            $captchaKey = $this->_captchaKey . getIp();
            if (strtolower($captcha) !== $redis->get($captchaKey)) {
                $this->exitJson(3, '验证码不正确或已失效');
            }
            $redis->del($captchaKey);
            $b = new B();
            $userRow = $b->dbGetOne('user', 'uid,username,password', ['username' => $username, 'status' => 1]);
            if (!$userRow) {
                $this->exitJson(2, '用户或密码不正确');
            }
            if (!password_verify($password, $userRow['password'])) {
                $this->exitJson(2, '用户或密码不正确');
            }

            unset($userRow['password']);
            // 账号信息
            $redis->hMset($this->_userInfoKey . $userRow['uid'], $userRow);
            $redis->expire($this->_userInfoKey . $userRow['uid'], $this->_userInfoKeyExpires);
            // Token
            $accessToken = $this->makeAccessToken($userRow['uid']);
            if (!$accessToken) {
                $this->exitJson(4, '登录失败');
            }
            $this->exitJson(0, '登录成功', ['uid' => $userRow['uid'], 'access_token' => $accessToken]);
        } else {
            header('Location: ' . DOMAIN . 'home/login.html');
        }
    }

    /**
     * 登出
     */
    public function logout()
    {
        parent::checkLogin();
        $redis = redis();
        $redis->del($this->_accessTokenKey . $this->_loginUid);
        $redis->del($this->_userInfoKey . $this->_loginUid);
        $this->exitJson(0, '登出成功');
    }

}
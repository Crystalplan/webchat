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
        parent::__construct();
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
        Log::write('============>: ' . $val);
        $redis->setex($key, 60, $val);
    }

    /**
     * 登录页
     */
    public function index()
    {
        if (request()->isAjax()) {
            // todo
            $post = trimArray(input('post.'));
            $validate = validate('Login');
            if (!$validate->scene('index')->check($post)) {
                echo $validate->getError();
            }
            $username = $post['username'];
            $password = aes_decrypt($post['password']);
            if (!$password) {
                $this->exitJson('密码格式不正确');
            }
            $captcha = $post['captcha'];
            $redis = redis();
            $captchaKey = $this->_captchaKey . getIp();
            if ($captcha !== $redis->get($captchaKey)) {
                $this->exitJson('验证码不正确或已失效');
            }
            $redis->del($captchaKey);
            $b = new B();
            $userRow = $b->dbGetOne('user', 'uid,password', ['username' => $username, 'status' => 1]);
            if (!$userRow) {
                $this->exitJson('用户或密码不正确');
            }
            if (!password_verify($password, $userRow['password'])) {
                $this->exitJson('用户或密码不正确');
            }
            $this->exitJson('登录成功', 0, ['uid' => $userRow['uid']]);
        } else {
            header('Location: ' . DOMAIN . 'home/login.html');
        }
    }

}
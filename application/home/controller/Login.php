<?php

/**
 * 登录
 * User: sky
 * Date: 2018/6/29
 * Time: 13:57
 */

namespace app\home\controller;

use \captcha\Captcha;

class Login extends Base
{
    /**
     * 获取验证码
     */
    public function getCaptcha()
    {
        session_start();
        $captcha = new Captcha();
        $captcha->doimg();
        $_SESSION['captcha'] = $captcha->getCode();//验证码保存到SESSION中
    }

    /**
     * 登录页
     */
    public function index()
    {
        if (request()->isAjax()) {
            // todo
        } else {
            return $this->fetch('index');
        }
    }

}
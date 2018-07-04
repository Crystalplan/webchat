<?php
/**
 * User: sky
 * Date: 2018/7/4
 * Time: 11:41
 */

namespace app\home\validate;

use think\Validate;

class Login extends Validate
{
    protected $rule = [
        ['username', 'require|max:20', '请输入用户名|用户名不能超过20个字符'],
        ['password', 'require', '请输入密码'],
        ['captcha', 'require|number', '请输入验证码|验证码必须为数字'],
    ];

    protected $scene = [
        'index' => ['username', 'password', 'captcha'],
    ];
}
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
    protected $_sessionPrefix = 'chat_';
    protected $_captchaKey = 'chat:home:captcha:';

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 返回json数据
     */
    function exitJson($errmsg, $errcode = -1, $data = [])
    {
        $return = ['errcode' => $errcode, 'errmsg' => $errmsg, 'data' => $data];
        $json = json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Log::write('F: ' . __FUNCTION__ . ', echo: ' . $json);
        exit($json);
    }
}
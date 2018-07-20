<?php
/**
 * User: sky
 * Date: 2018/7/9
 * Time: 11:42
 */

namespace app\home\controller;

use think\controller\Yar;
use think\Log;

class Rpc extends Yar
{
    /**
     * 必不可少 yar实例化的入口文件
     */
    public function index()
    {
    }

    /**
     * 处理自定义的方法
     * @param $param ['data'] 待处理的数据
     * @param $param ['rtime'] 时间
     * @param $param ['sign']  签名
     * @return array
     */
    public function customized($param)
    {
        $signFlag = rpcValidateSign($param);
        if ($signFlag == true) {
            $data = json_decode($param['data'], true);
            $function = $data['function'];
            switch ($function) {

            }
        } else {
            Log::logger('customized', json_encode($param) . ' res:' . $signFlag['msg'], 'rpc_error');
        }
    }
}
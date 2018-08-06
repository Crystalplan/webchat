<?php
/**
 * 好友分组
 * User: sky
 * Date: 2018/7/27
 * Time: 9:04
 */

namespace app\home\controller;

use app\home\model\Fgroup as M;

class Fgroup extends Base
{

    /**
     * 获取分组列表
     */
    public function getList()
    {
        $m = new M();
        $data = $m->getList(['uid' => $this->loginUid]);
        if (!$data) {
            $this->exitJson(0, '', []);
        }
        $this->exitJson(0, '', $data, ['domain' => FILES_DOMAIN]);
    }
}
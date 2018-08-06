<?php
/**
 * 群组
 * User: sky
 * Date: 2018/7/26
 * Time: 16:40
 */

namespace app\home\controller;

use app\home\model\Group as M;

class Group extends Base
{

    /**
     * 获取用户群列表
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
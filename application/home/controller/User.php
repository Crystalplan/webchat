<?php
/**
 * User: sky
 * Date: 2018/7/26
 * Time: 11:25
 */

namespace app\home\controller;

use app\home\model\Base as B;

class User extends Base
{
    /**
     * 获取用户资料
     */
    public function getInfo()
    {
        $b = new B();
        $userRow = $b->dbGetOne(DB_USER, 'username,image,sex,age,signature', ['uid' => $this->loginUid, 'status' => 1]);
        if (!$userRow) {
            $this->exitJson(8, '没有数据');
        }
        $userRow['image'] = FILES_DOMAIN . $userRow['image'];
        $this->exitJson(0, '', $userRow);
    }
}
<?php
/**
 * 群组
 * User: sky
 * Date: 2018/7/26
 * Time: 16:42
 */

namespace app\home\model;

use think\Db;

class Group extends Base
{
    /**
     * 获取用户群列表
     */
    public function getList($where)
    {
        return Db::name(DB_USER_GROUP)->alias('a')
            ->join(DB_GROUP . ' b', 'a.gid = b.id', 'left')
            ->where(['a.uid' => $where['uid'], 'a.status' => 1, 'b.status' => 1])
            ->field('b.name,b.icon')
            ->select();
    }

}
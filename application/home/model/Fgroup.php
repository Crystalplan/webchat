<?php
/**
 * 好友分组
 * User: sky
 * Date: 2018/7/27
 * Time: 9:07
 */

namespace app\home\model;

use think\Db;

class Fgroup extends Base
{
    /**
     * 获取分组列表
     */
    public function getList($where)
    {
        $result = Db::name(DB_USER_FRIEND)->alias('a')
            ->join(DB_FGROUP . ' b', 'a.fgid = b.id', 'left')
            ->join(DB_USER . ' c', 'a.fuid = c.uid', 'left')
            ->where(['a.uid' => $where['uid'], 'a.status' => 1, 'b.status' => 1, 'c.status' => 1])
            ->field('a.fgid, a.fuid, b.name, c.username, c.image')
            ->select();
        $data = [];
        foreach ($result as $row) {
            $data[$row['fgid']]['name'] = $row['name'];
            $data[$row['fgid']]['subData'][] = [
                'fuid' => $row['fuid'],
                'username' => $row['username'],
                'image' => $row['image']
            ];
        }
        return $data;

    }

}
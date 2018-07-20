<?php
/**
 * User: sky
 * Date: 2018/7/3
 * Time: 11:25
 */

namespace app\home\model;

use think\Db;
use think\Model;

class Base extends Model
{
    /**
     * 新增
     * @param $table   string 表
     * @param $insert  array 新增数据
     * @return int
     */
    public function dbInsert($table, $insert)
    {
        return Db::name($table)->insertGetId($insert);
    }

    /**
     * 批量新增
     * @param $table   string 表
     * @param $insert  array 新增数据
     * @return int
     */
    public function dbInsertAll($table, $insert)
    {
        return Db::name($table)->insertAll($insert);
    }

    /**
     * 更新
     * @param $table   string 表
     * @param $update  array 更新数据
     * @param $where   array 更新条件
     * @param $limit   int 限制个数
     * @return int
     */
    public function dbUpdate($table, $update, $where, $limit = '')
    {
        $db = Db::name($table);
        if ($limit !== '') {
            $db->limit($limit);
        }
        return $db->where($where)->update($update);
    }

    /**
     * 获取单个记录数据
     */
    public function dbGetOne($table, $field, $where)
    {
        return Db::name($table)->where($where)->field($field)->find();
    }

    /**
     * 获取多个记录数据
     */
    public function dbGetResult($table, $field, $where)
    {
        return Db::name($table)->where($where)->field($field)->select();
    }
}
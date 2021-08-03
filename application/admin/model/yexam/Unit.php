<?php

namespace app\admin\model\yexam;

use think\Model;

class Unit extends Model
{
    // 表名
    protected $name = 'yexam_unit';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "createtime";
    protected $updateTime = false;

    protected static function init()
    {

        //添加章节，设置上级章节为非终极章节
        self::afterInsert(function ($row) {
            $row->getQuery()->where('id', $row['pid'])->update(array('is_last'=>0));

        });


        //编辑章节设置上级章节和原始上级章节，判断是否为终极章节
        self::beforeUpdate(function ($row) {

            $unit = Unit::get($row['id']);
            if($row->getQuery()->where('pid', $unit['pid'])->count() == 1){
                $row->getQuery()->where('id', $unit['pid'])->update(array('is_last'=>1));
            }
        });

        //编辑章节设置上级章节为非终极章节
        self::afterUpdate(function ($row) {
            $row->getQuery()->where('id', $row['pid'])->update(array('is_last'=>0));
        });

        //删除章节判断上级章节是否还存在下级章节，不存在测设置为终极章节
        self::afterDelete(function ($row) {
            if($row->getQuery()->where('pid', $row['pid'])->count() == 0){
                $row->getQuery()->where('id', $row['pid'])->update(array('is_last'=>1));
            }
        });
    }
}

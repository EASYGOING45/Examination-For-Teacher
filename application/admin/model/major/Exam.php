<?php

namespace app\admin\model\Major;

use think\Model;
use think\Db;


class Exam extends Model
{
    // 表名
    protected $name = 'yexam_exam';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    // 获取最后一条正式考试信息
    public function getLatestExamInfo()
    {
        $examInfo = Db::name("yexam_exam")
                -> where("type",1)
                -> order("id","DESC")
                -> find();
        return $examInfo;
    }
  
}

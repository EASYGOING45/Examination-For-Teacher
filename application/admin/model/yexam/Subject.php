<?php

namespace app\admin\model\yexam;

use think\Model;

class Subject extends Model
{
    // 表名
    protected $name = 'yexam_subject';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "createtime";
    protected $updateTime = false;


}

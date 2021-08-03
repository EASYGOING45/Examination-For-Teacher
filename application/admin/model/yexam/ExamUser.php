<?php

namespace app\admin\model\yexam;

use think\Model;

class ExamUser extends Model
{
    // 表名
    protected $name = 'yexam_exam_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

}

<?php

namespace app\admin\model\yexam;

use think\Model;

class ExamUserLog extends Model
{
    // 表名
    protected $name = 'yexam_exam_user_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

}

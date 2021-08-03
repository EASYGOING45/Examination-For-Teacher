<?php

namespace app\admin\model\yexam;

use think\Model;

class ExamConfig extends Model
{
    // 表名
    protected $name = 'yexam_exam_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
//    protected $append = [
//        'type_text',
//        'create_time_text',
//        'givetime_text'
//    ];

}

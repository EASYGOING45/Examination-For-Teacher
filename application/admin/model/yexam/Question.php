<?php

namespace app\admin\model\yexam;

use think\Model;

class Question extends Model
{
    // 表名
    protected $name = 'yexam_question';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'type_text'
    ];

    
    public function getTypeList()
    {
        return ['1' => __('单选'),'2' => __('多选'),'3' => __('判断')];
    }


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

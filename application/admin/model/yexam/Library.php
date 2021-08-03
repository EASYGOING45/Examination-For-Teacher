<?php

namespace app\admin\model\yexam;

use think\Model;

class Library extends Model
{
    // 表名
    protected $name = 'yexam_library';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "createtime";
    protected $updateTime = false;

    public function subject()
    {
        return $this->belongsTo('Subject', 'subject_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 更新题库题目数量
     */
    public static function refresh_num($library_id){
        $libraryModel = new Library();
        $libraryInfo = $libraryModel->where(['id'=>$library_id])->find();

        if($libraryInfo){
            $questionModel = new Question();
            $num = $questionModel->where(['id'=>['in',$libraryInfo['question_ids']]])->count();

            $libraryInfo->save(['num'=>$num]);
        }

    }

}

<?php

namespace app\admin\model\Major;

use think\Model;
use think\Db;

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

    ];

     /**
     * 获取题目详情
     */
    public function getQuestion($question_id){

        $question = Db::name("yexam_question")
                    ->field("id,question_name,type,right_answer,area")
                    ->where(['id'=>$question_id])
                    ->find();
        if(empty($question)){
            return [];
        }else{
            //获取答案
            if($question['type'] == 3){
                $question['answers'] = array(
                    '0' => array(
                        'answer' => "对",
                        'answer_code' => "1",
                        'id' => 1,
                        'question_id' => $question['id']
                    ),
                    '1' => array(
                        'answer' => "错",
                        'answer_code' => "0",
                        'id' => 2,
                        'question_id' => $question['id']
                    ),

                );
            }else{
                $question['answers'] = Db::name("yexam_answers")->where(['question_id'=>$question['id']])->select();
            }
            $question['is_fav'] = 0;
        }

        return $question;
    }
  
}

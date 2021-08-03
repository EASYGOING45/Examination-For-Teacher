<?php

namespace app\admin\model\yexam;

use think\Model;

class Exam extends Model
{
    // 表名
    protected $name = 'yexam_exam';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "createtime";
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'type_text',
        'create_time_text',
        'givetime_text'
    ];
    

    public function getTypeList()
    {
        return ['1' => __('正式考试'),'2' => __('模拟考试')];
    }     


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getGivetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['givetime']) ? $data['givetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setGivetimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    /**
     * 更新考试题库
     */
    public static function refresh_question($exam_id){
        $examModel = new Exam();
        $examInfo = $examModel->where(['id'=>$exam_id])->find();

        if($examInfo){
            $examConfigModel = new ExamConfig();
            $examConfig = $examConfigModel->where(['exam_id'=>$examInfo['id']])->select();
            $score = [];
            foreach($examConfig as $k=>$v){
                $score[$v['type']] = $v['score'];
            }

            $total_score = 0;
            $total_num = 0;
            $num = ['1'=>0,'2'=>0,'3'=>0];
            $questionModel = new Question();
            $questionList = $questionModel->where(['id'=>['in',$examInfo['question_ids']]])->select();

            foreach ($questionList as $k=>$v){
                $total_score += $score[$v['type']];
                $total_num++;
            }

            foreach($num as $k=>$v){
                $examConfigModel = new ExamConfig();
                $examConfigModel->where(['exam_id'=>$examInfo['id'],'type'=>$k])->update(['num'=>$v]);
            }

            $examInfo->save(['score'=>$total_score,'num'=>$total_num]);
        }

    }
}

<?php

namespace app\api\controller\yexam;

use app\common\controller\Api;

/**
 * 科目
 */
class Subject extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['index','unit'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    /**
     * 获取科目列表
     */
    public function index()
    {
        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $subject = new \addons\yexam\service\Subject();
        $data = $subject->getSubjectList($page,$limit);

        $this->success('请求成功', $data);
    }

    /**
     * 获取科目下的章节，分级返回
     */
    public function unit(){
        $subject_id = $this->request->post('subject_id');
        $unit_id = $this->request->post('unit_id',0);

        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $subject = new \addons\yexam\service\Subject();
        $data = $subject->getUnitList($subject_id,$unit_id,$page,$limit);
        foreach($data['data'] as &$v){

            $unit = new \addons\yexam\service\Unit();
            $v['scale'] = $this->auth->id>0?$unit->right_scale($v['id'],$this->auth->id):0;     //正确率
            $v['total_num'] = $unit->getUnitQuestionNum($v['id']);          //章节题目总数
            $v['test_num'] = $this->auth->id>0?$unit->getUnitQuestionTestNum($v['id'],$this->auth->id):0;       //章节题目已答题
        }
        $this->success('请求成功', $data);
    }

    /**
     *获取指定科目下的练习情况
     */
    public function question_info(){
        $subject_id = $this->request->post('subject_id');

        $subject = new \addons\yexam\service\Subject();

        $total = $subject->getSubjectQuestionNum($subject_id);  //总数
        $test_num = $subject->getSubjectQuestionTestNum($subject_id,$this->auth->id);   //练习数量
        $right_num = $subject->getSubjectQuestionRightNum($subject_id,$this->auth->id); //正确数量

        $this->success('请求成功',['total'=>$total,'test_num'=>$test_num,'right_num'=>$right_num,'scale'=>empty($right_num)?0:(intval($right_num/$test_num,2)*100)]);
    }



}

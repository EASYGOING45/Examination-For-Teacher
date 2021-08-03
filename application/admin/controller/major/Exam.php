<?php

namespace app\admin\controller\major;

use app\common\controller\Backend;
use think\Config;
use think\Db;
use app\admin\model\major\Question as QuestionModel;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Exam extends Backend
{
    
    /**
     * Index模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new  \app\admin\model\major\Exam();

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        //获取管理员ID，之后判断是否进行报名
        $adminId = $this->auth->id;
        // $adminId = 22;
        //基础信息
        $basicInfo = Db::view("admin","username,id")
                    -> view("teacher_detail","YXDM,XM,XBDM,ID","admin.username = teacher_detail.ID")
                    -> view("dict_college","YXJC,YXDM","teacher_detail.YXDM = dict_college.YXDM")
                    -> view("fdy_type","GH,XM,type","admin.username = fdy_type.GH")
                    -> where("admin.id",$adminId)
                    -> find();
        if (empty($basicInfo)) {
            $this->error("信息不存在，请联系管理员");
        }
        //判断是否报名
        $checkSign = Db::name("fdy_major")->where("GH",$basicInfo["username"])->find();
        if (empty($checkSign)) {
            //未报名
            $this->error("请先报名考试!");
        }

        // 若传递考试id则跳转到对应的考试,若没有则跳转到考试列表界面
        $examId = $this->request->param("examId");
        if(empty($examId)){
            $examData = $this->getexam();
            return $this->view->fetch("examlist",["examList"=>$examData]);
        }
        // 获取最后一个正式考试的信息
        $examInfo = $this->model->getLatestExamInfo();
        if (time() < strtotime($examInfo["start_date"])) {
            $this->error("考试时间未开始，考试开始时间为".$examInfo["start_date"]);
        }
        if (time() > strtotime($examInfo["end_date"]) ) {
            $this->error("考试已经结束");
        }

        // 获取题目详细信息
        $QuestionModel = new QuestionModel();
        $questionIdList = $examInfo["question_ids"];
        $questionList = [];
        if (empty($questionIdList)) {
            $questionList = [];
        } else {
            $questionIdListArray = explode(",",$questionIdList);
            foreach ($questionIdListArray as  $questionId) {
                $temp = $QuestionModel->getQuestion($questionId);
                $questionList[] = $temp;
            }
        }
        foreach ($questionList as $key => $value) {
            if (empty($value)) {
                unset($questionList[$key]);
            }
        }
        return $this->view->fetch("index",["questionList"=>$questionList]);

    }


    /**
     * 获取考试列表
     */
    public function getexam()
    {
        $subject_id = 1;
        // $page = $this->request->post('page');
        // $limit = $this->request->post('limit');

        $examData = Db::name("yexam_exam")->where(['status'=>1,'type'=>1,'subject_id'=>$subject_id, 'start_date'=>['elt',date("Y-m-d H:i:s",time())],
            'end_date'=>['egt',date("Y-m-d H:i:s",time())]])->order("sort asc")->select();;

        foreach($examData as &$v){
            //查看是否已经参加考试了
            if(Db::name("yexam_exam_user")->where(['exam_id'=>$v['id'],'user_id'=>$this->auth->id,'up_status'=>2])->find()){
                $v['is_allow'] = 0;
            }else{
                $v['is_allow'] = 1;
            }
        }
        return $examData;

    }

    /**
     * 获取题目列表
     */
    public function getquestion()
    {
        // 获取最后一个正式考试的信息
        $examInfo = $this->model->getLatestExamInfo();
        // 获取题目详细信息
        $QuestionModel = new QuestionModel();
        $questionIdList = $examInfo["question_ids"];
        $questionList = [];
        if (empty($questionIdList)) {
            $questionList = [];
        } else {
            $questionIdListArray = explode(",",$questionIdList);
            foreach ($questionIdListArray as  $questionId) {
                $temp = $QuestionModel->getQuestion($questionId);
                $questionList[] = $temp;
            }
        }
        foreach ($questionList as $key => $value) {
            if (empty($value)) {
                unset($questionList[$key]);
            }
        }
        // 
        // $this->success("success","",["list"=>$questionList,"count"=>count($questionList)]);
        return json(["list"=>$questionList,"count"=>count($questionList)]);

    }


}

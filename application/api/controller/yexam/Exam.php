<?php

namespace app\api\controller\yexam;

use app\admin\model\yexam\ExamUser;
use app\common\controller\Api;

/**
 * 考试接口
 */
class Exam extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    /**
     * 获取模拟考试列表
     */
    public function virtual()
    {
        $subject_id = $this->request->post('subject_id');
        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $examModel = new \addons\yexam\service\Exam();
        $data = $examModel->getExamList(['status'=>1,'type'=>2,
            'subject_id'=>$subject_id,
            'start_date'=>['elt',date("Y-m-d H:i:s",time())],
            'end_date'=>['egt',date("Y-m-d H:i:s",time())]
        ],$page,$limit);

        foreach ($data['data'] as &$v){
            $v['is_allow'] = 1;
        }

        $this->success('请求成功', $data);
    }

    /**
     * 获取正式考试列表
     */
    public function index()
    {
        $subject_id = $this->request->post('subject_id');
        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $examModel = new \addons\yexam\service\Exam();
        $data = $examModel->getExamList(['status'=>1,'type'=>1,'subject_id'=>$subject_id, 'start_date'=>['elt',date("Y-m-d H:i:s",time())],
            'end_date'=>['egt',date("Y-m-d H:i:s",time())]],$page,$limit);

        foreach($data['data'] as &$v){
            //查看是否已经参加考试了
            $examUserModel = new ExamUser();
            if($examUserModel->where(['exam_id'=>$v['id'],'user_id'=>$this->auth->id,'up_status'=>2])->find()){
                $v['is_allow'] = 0;
            }else{
                $v['is_allow'] = 1;
            }
        }
        $this->success('请求成功', $data);
    }

    /**
     * 获取考试答题卡
     */
    public function card(){
        $exam_id = $this->request->post('exam_id');

        $exam = new \addons\yexam\service\Exam();
        $data = $exam->getCard($exam_id,$this->auth->id);
        if($data){
            $this->success('请求成功', $data);
        }else{
            $this->error($exam->error);
        }

    }



    /**
     * 获取考试记录
     */
    public function record(){
        $subject_id = $this->request->post('subject_id');
        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $exam = new \addons\yexam\service\Exam();
        $data = $exam->getRecordList($subject_id,$this->auth->id,$page,$limit);

        $this->success('请求成功', $data);
    }

    /**
     * 定位到上次考试答题位置
     */
    public function position(){
        $exam_id = $this->request->post('exam_id');

        $unit = new \addons\yexam\service\Exam();
        $this->success('请求成功',$unit->getLastPosition($exam_id,$this->auth->id));
    }

    /**
     * 获取考试题目详情
     * @throws \think\Exception
     */
    public function question(){
        $exam_id = $this->request->post('exam_id');
        $question_id = $this->request->post('question_id');

        $question = new \addons\yexam\service\Exam();
        $data = $question->getQuestion($exam_id,$question_id,$this->auth->id);
        if($data){
            $this->success('请求成功',$data);
        }else{
            $this->error($question->error);
        }

    }

    /**
     * 开始考试
     */
    public function begin(){
        $exam_id = $this->request->post('exam_id');

        $exam = new \addons\yexam\service\Exam();
        $result = $exam->begin($exam_id,$this->auth->id);
        if($result){
            $this->success('请求成功',['endtime'=>$result]);
        }else{
            $this->error($exam->error);
        }
    }

    /**
     * 考试答题
     */
    public function answer(){
        $exam_id = $this->request->post('exam_id');
        $question_id = $this->request->post('question_id');
        $answer = $this->request->post('answer');

        $exam = new \addons\yexam\service\Exam();
        $result = $exam->answer($exam_id,$this->auth->id,$question_id,$answer);
        if($result){
            $this->success('请求成功');
        }else{
            $this->error($exam->error);
        }
    }

    /**
     * 交卷
     */
    public function up(){
        $exam_id = $this->request->post('exam_id');

        $exam = new \addons\yexam\service\Exam();
        $result = $exam->up($exam_id,$this->auth->id);
        if($result){
            $this->success($result);
        }else{
            $this->error($exam->error);
        }
    }

    /**
     * 错题记录
     */
    public function error_log(){
        $type = $this->request->post("type",1);
        $subject_id = $this->request->post("subject_id",0);
        $page = $this->request->post("page");
        $limit = $this->request->post("limit");
        $unit = new \addons\yexam\service\Exam();

        if(empty($subject_id)){
            $this->error('科目ID异常');
        }

        $data = $unit->getErrorLogList($subject_id,$type,$this->auth->id,$page,$limit);
        $this->success('请求成功',$data);
    }

    /**
     * 获取考试错题答题卡
     */
    public function error_card()
    {
        $exam_user_id = $this->request->post('exam_user_id');

        $subject = new \addons\yexam\service\Exam();
        $data = $subject->getErrorCard($exam_user_id,$this->auth->id);

        $this->success('请求成功', $data);
    }

}

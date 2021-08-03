<?php

namespace app\api\controller\yexam;

use app\admin\model\yexam\QuestionLog;
use app\common\controller\Api;

/**
 * 题库接口
 */
class Library extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['index'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    /**
     * 获取历年真题列表
     * @throws \think\Exception
     */
    public function index()
    {
        $subject_id = $this->request->post('subject_id');
        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $subject = new \addons\yexam\service\Library();
        $data = $subject->getLibraryList($subject_id,$page,$limit);
        foreach($data['data'] as &$v){

            $unit = new \addons\yexam\service\Library();
            $v['scale'] = $unit->right_scale($v,$this->auth->id);     //正确率
            $v['total_num'] = $unit->getLibraryQuestionCount($v);          //题目总数
            $v['test_num'] = $unit->getLibraryQuestionTestNum($v,$this->auth->id);       //题目已答题
        }
        $this->success('请求成功', $data);
    }

    /**
     * 获取答题卡
     */
    public function card()
    {
        $library_id = $this->request->post('library_id');

        $subject = new \addons\yexam\service\Library();
        $data = $subject->getCard($library_id,$this->auth->id);

        $this->success('请求成功', $data);
    }

    /**
     * 清空章节练习记录
     */
    public function emptytestlog(){
        $request = $this->request;
        $library_id = $request->post("library_id",0);

        $logModel = new QuestionLog();
        $logModel->where(['user_id'=>$this->auth->id,'library_id'=>$library_id])->delete();

        $this->success('请求成功！');
    }


    /**
     * 定位到上次历年真题位置
     */
    public function position(){
        $library_id = $this->request->post('library_id');

        $unit = new \addons\yexam\service\Library();
        $this->success('请求成功',$unit->getLastPosition($library_id,$this->auth->id));
    }

    /**
     * 收藏
     */
    public function fav(){
        $id = $this->request->post("id",0);
        $question = new \addons\yexam\service\Question();

        $result = $question->favQuestion($id,$this->auth->id);
        switch ($result){
            case "1":
                $this->success('取消收藏成功');
                break;
            case "2":
                $this->success('收藏成功');
                break;
        }
    }


    /**
     * 答题操作
     */
    public function ansqueedit(){
        $id = $this->request->post("id",0);
        $answer = $this->request->post("answer","");

        $unit = new \addons\yexam\service\Library();
        $data = $unit->doanswer($id,$answer,$this->auth->id);
        $this->success('请求成功',$data);
    }


    /**
     * 错题记录
     */
    public function error_log(){
        $subject_id = $this->request->post("subject_id",0);
        $page = $this->request->post("page");
        $limit = $this->request->post("limit");
        $unit = new \addons\yexam\service\Library();

        if(empty($subject_id)){
            $this->error('科目ID异常');
        }

        $data = $unit->getErrorLogList($subject_id,$this->auth->id,$page,$limit);
        $this->success('请求成功',$data);
    }

    /**
     * 收藏答题卡
     */
    public function fav_card(){
        $library_id = $this->request->post("library_id",0);

        $unit = new \addons\yexam\service\Library();
        $data = $unit->getFavCard($library_id,$this->auth->id);

        $this->success('请求成功',$data);
    }

    /**
     * 错题答题卡
     */
    public function error_card(){
        $library_id = $this->request->post("library_id",0);

        $unit = new \addons\yexam\service\Library();
        $data = $unit->getErrorCard($library_id,$this->auth->id);

        $this->success('请求成功',$data);
    }

    /**
     * 移除错题
     */
    public function remove_error(){
        $id = $this->request->post('id');
        $library = new \addons\yexam\service\Library();
        if($library->removeErrorQuestion($id,$this->auth->id)){
            $this->success('请求成功');
        }else{
            $this->error('请求失败');
        }

    }



    /**
     * 收藏列表
     */
    public function fav_list(){
        $subject_id = $this->request->post("subject_id",0);
        $page = $this->request->post("page");
        $limit = $this->request->post("limit");
        if(empty($subject_id)){
            $this->error('科目ID异常');
        }

        $unit = new \addons\yexam\service\Library();
        $data = $unit->getFavList($subject_id,$this->auth->id,$page,$limit);

        $this->success('请求成功',$data);
    }



}

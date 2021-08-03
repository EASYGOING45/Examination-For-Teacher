<?php

namespace app\api\controller\yexam;

use app\admin\model\yexam\QuestionLog;
use app\common\controller\Api;

/**
 * 章节练习接口
 */
class Unit extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    /**
     * 获取答题卡
     */
    public function card()
    {
        $unit_id = $this->request->post('unit_id');

        $subject = new \addons\yexam\service\Question();
        $data = $subject->getTestCard($unit_id,$this->auth->id);

        $this->success('请求成功', $data);
    }

    /**
     * 定位到上次
     */
    public function position(){
        $unit_id = $this->request->post('unit_id');

        $unit = new \addons\yexam\service\Unit();
        $this->success('请求成功',$unit->getLastPosition($unit_id,$this->auth->id));
    }



    /**
     * 清空章节练习记录
     */
    public function emptytestlog(){
        $request = $this->request;
        $unit_id = $request->post("unit_id",0);

        $logModel = new QuestionLog();
        $logModel->where(['user_id'=>$this->auth->id,'unit_id'=>$unit_id])->delete();

        $this->success('请求成功！');
    }

    /**
     * 答题操作
     */
    public function ansqueedit(){
        $id = $this->request->post("id",0);
        $answer = $this->request->post("answer","");

        $unit = new \addons\yexam\service\Unit();
        $data = $unit->doanswer($id,$answer,$this->auth->id);
        $this->success('请求成功',$data);
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
     * 错题记录
     */
    public function error_log(){
        $subject_id = $this->request->post("subject_id",0);
        $page = $this->request->post("page");
        $limit = $this->request->post("limit");
        $unit = new \addons\yexam\service\Unit();

        if(empty($subject_id)){
            $this->error('科目ID异常');
        }

        $data = $unit->getErrorLogList($subject_id,$this->auth->id,$page,$limit);
        foreach($data['data'] as &$v){
            $unit = new \addons\yexam\service\Unit();
            $arr = [];
            $v['parent_unit'] = implode("-",array_reverse($unit->getParentNames($v['id'],$arr)));
        }
        $this->success('请求成功',$data);
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

        $unit = new \addons\yexam\service\Unit();
        $data = $unit->getFavList($subject_id,$this->auth->id,$page,$limit);
        foreach($data['data'] as &$v){
            $unit = new \addons\yexam\service\Unit();
            $arr = [];
            $v['parent_unit'] = implode("-",array_reverse($unit->getParentNames($v['id'],$arr)));
        }
        $this->success('请求成功',$data);
    }

    /**
     * 收藏答题卡
     */
    public function fav_card(){
        $unit_id = $this->request->post("unit_id",0);

        $unit = new \addons\yexam\service\Unit();
        $data = $unit->getFavCard($unit_id,$this->auth->id);

        $this->success('请求成功',$data);
    }



    /**
     * 错题答题卡
     */
    public function error_card(){
        $unit_id = $this->request->post("unit_id",0);

        $unit = new \addons\yexam\service\Unit();
        $data = $unit->getErrorCard($unit_id,$this->auth->id);

        $this->success('请求成功',$data);
    }

    /**
     * 移除错题
     */
    public function remove_error(){
        $id = $this->request->post('id');
        $unit = new \addons\yexam\service\Unit();
        if($unit->removeErrorQuestion($id,$this->auth->id)){
            $this->success('请求成功');
        }else{
            $this->error('请求失败');
        }
    }

    /**
     * 获取我的错题题目详情,传流水号
     */
    public function error_question(){
        $unit_id = $this->request->post('unit_id');
        $num = $this->request->post('num');
        if($num<=0){
            $this->success('请求成功',[]);
        }
        $unit = new \addons\yexam\service\Unit();
        $data = $unit->getErrorNumQuestion($unit_id,$num,$this->auth->id);
        $this->success('请求成功',$data);
    }


}

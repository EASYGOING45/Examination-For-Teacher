<?php

namespace app\admin\controller\yexam;

use addons\yexam\library\makezip\Makezip;
use addons\yexam\library\Util;
use app\admin\model\yexam\Answer;
use app\admin\model\yexam\ExamUser;
use app\admin\model\yexam\ExamUserLog;
use app\common\controller\Backend;

use app\common\model\User;
use fast\Tree;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Exception;
use think\Request;


/**
 * 测验管理
 *
 * @icon fa fa-circle-o
 */
class Exam extends Backend
{

    protected $model = null;
    protected $noNeedRight = ["refresh_tree","delquestion",'has_ques',"delquestion","add_exam_ques","del_exam_ques","addquestion","editquestion"];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yexam\Exam;

        $subjectModel = new \app\admin\model\yexam\Subject();
        $subjectList = $subjectModel->where(['status'=>1])->select();

        $this->view->assign('subjectList', $subjectList);

    }


    /**
     * 查看考试列表
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            $type = $this->request->param('type',1);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($where)
                ->where(['type'=>$type])
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where(['type'=>$type])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach($list as $k=>$v){
                $subject = new \app\admin\model\yexam\Subject();
                $subjectInfo = $subject->where(array('id'=>$v['subject_id']))->find();
                $list[$k]['subject_name'] = $subjectInfo['subject_name'];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        $chanelList = [];
        $subjectModel = new \app\admin\model\yexam\Subject();
        $subjectList = $subjectModel->order("weigh asc")->select();
        foreach($subjectList as $k=>$v){
            $chanelList[$k] = ['id'=>$v['id'],'text'=>$v['subject_name'],'parent'=>'#',"type"=>"list",'state'=>['opened'=>false]];
        }
        $this->assignconfig("channelList", $chanelList);
        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add()
    {
        //echo "是这里！";
    echo "hello worldld";
        if ($this->request->isPost()) {
            echo "hello world";
            $params = $this->request->post("row/a");
            $num = array('1'=>0,'2'=>0,'3'=>0);
            $score = array('1'=>$params['score'][1],'2'=>$params['score'][2],'3'=>$params['score'][3]);
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }

                    $subject_id = $params['subject_id'];
                    if(empty($subject_id)){
                        $this->error('请选择科目');exit;
                    }

                    $givetime = $params['givetime'];
                    if($givetime <=0 ){
                        $this->error('请输入正确的开始时长');exit;
                    }
                    foreach($score as $k=>$v){
                        if($v <=0 ){
                            $this->error('题目分数不能为0');exit;
                        }
                    }
                    unset($params['num']);
                    unset($params['score']);

                    $result = $this->model->allowField(true)->save($params);

                    if ($result !== false) {

                        foreach($num as $k=>$v){
                            $configModel = new \app\admin\model\yexam\ExamConfig();
                            $configModel->data(array('exam_id'=> $this->model->id,'type'=>$k,'score'=>$score[$k],'num'=>$v))->save();
                        }
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
    }


    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $num = array('1'=>0,'2'=>0,'3'=>0);
            $score = array('1'=>$params['score'][1],'2'=>$params['score'][2],'3'=>$params['score'][3]);
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    unset($params['num']);
                    unset($params['score']);

                    $givetime = $params['givetime'];
                    if($givetime <=0 ){
                        $this->error('请输入正确的开始时长');exit;
                    }
                    foreach($score as $k=>$v){
                        if($v <=0 ){
                            $this->error('题目分数不能为0');exit;
                        }
                    }


                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $configModel = new \app\admin\model\yexam\ExamConfig();
                        $configModel->where(['exam_id'=> $row->id])->delete();

                        foreach($num as $k=>$v){
                            $configModel = new \app\admin\model\yexam\ExamConfig();
                            $configModel->save(array('exam_id'=> $row->id,'type'=>$k,'score'=>$score[$k],'num'=>$v));
                        }
                        \app\admin\model\yexam\Exam::refresh_question($row->id);
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $configModel = new \app\admin\model\yexam\ExamConfig();
        $configs = $configModel->where(array('exam_id'=>$ids))->select();

        $this->assign('configs',$configs);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            foreach ($list as $k => $v) {
                if($v['id']>4){
                    $count += $v->delete();
                }

            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }


    /**
     * 查看考试题目
     */
    public function question()
    {
        $exam_id = $this->request->get("exam_id");
        $exam = new \app\admin\model\yexam\Exam();
        $examInfo =$exam->where(array('id'=>$exam_id))->find();
        $this->assign('examInfo',$examInfo);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $model = new \app\admin\model\yexam\Question();
            $question_ids = $examInfo['question_ids'];

            $total = $model
                ->where($where)
                ->where('id','in',$question_ids)
                ->order($sort, $order)
                ->count();

            $list = $model
                ->where($where)
                ->where('id','in',$question_ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 查看
     */
    public function has_ques()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $exam_id = $this->request->get("exam_id");
            $type = $this->request->get("type");
            $tid = $this->request->get("tid");

            $exam = new  \app\admin\model\yexam\Exam();
            $examInfo =$exam->where(array('id'=>$exam_id))->find();

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            switch ($type){
                case "1":
                    $_where = ['id'=>['in',empty($examInfo['question_ids'])?0:$examInfo['question_ids']]];

                    if(!empty($tid)){
                        $unitModel = new \app\admin\model\yexam\Unit();
                        $unitlist = collection($unitModel->where(['subject_id'=>$examInfo['subject_id']])->select())->toArray();
                        Tree::instance()->init($unitlist);
                        $unitlist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($tid), 'unit_name');

                        if(empty($unitlist)){
                            $_where['unit_id'] = $tid;
                        }else{
                            $unit_ids = [];
                            foreach($unitlist as $k=>$v){
                                $unit_ids[] = $v['id'];
                            }

                            $_where['unit_id'] = ['in',empty($unit_ids)?0:$unit_ids];
                        }
                    }else{
                        $result = array("total" => 0, "rows" => []);

                        return json($result);
                    }


                    break;
                case "2":

                    if(!empty($tid)){
                        $unitModel = new \app\admin\model\yexam\Unit();
                        $unitlist = collection($unitModel->where(['subject_id'=>$examInfo['subject_id']])->select())->toArray();
                        Tree::instance()->init($unitlist);
                        $unitlist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($tid), 'unit_name');

                        if(empty($unitlist)){
                            $_where['unit_id'] = $tid;
                        }else{
                            $unit_ids = [];
                            foreach($unitlist as $k=>$v){
                                $unit_ids[] = $v['id'];
                            }
                            $_where['unit_id'] = ['in',empty($unit_ids)?0:$unit_ids];
                        }
                    }else{
                        $result = array("total" => 0, "rows" => []);

                        return json($result);
                    }
                    break;
                case "3":
                    $_where = ['id'=>['in',empty($examInfo['question_ids'])?0:$examInfo['question_ids']]];
                    break;
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $model = new  \app\admin\model\yexam\Question();

            $total = $model
                ->where($where)
                ->where($_where)
                ->order($sort, $order)
                ->count();

            $list = $model
                ->where($where)
                ->where($_where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $question_ids = explode(",",$examInfo['question_ids']);

            foreach($list as $k=>$v){
                $list[$k]['checked'] = false;
                if(in_array($v['id'],$question_ids) && $type == 2){
                    $list[$k]['checked'] = true;
                }
            }

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 删除操作
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delquestion(){
        $ids = $this->request->post('ids');
        $exam_id = $this->request->get('exam_id');

        $exam = new \app\admin\model\yexam\Exam();
        $examInfo = $exam->where(array('id'=>$exam_id))->find();
        $question_ids_arr = explode(",",$examInfo['question_ids']);

        foreach(explode(",",$ids) as $id){
            if(!in_array($id,$question_ids_arr)){
                continue;
            }
            if(empty($id)){
                $this->error('参数错误');exit;
            }

            $user = new \app\admin\model\yexam\Question();
            $row = $user->where(['id'=>$id])->find();

            if($row){
                if($row['unit_id'] == 0){
                    $row->delete();
                }

                foreach($question_ids_arr as $k=>$v){
                    if($id == $v){
                        unset($question_ids_arr[$k]);
                    }
                }
            }

        }

        //更新考试题目
        $user = new \app\admin\model\yexam\Question();
        $ids = $user->where(['id'=>['in',$question_ids_arr]])->column('id');

        $examInfo->save(['question_ids'=>implode(",",$ids)]);

        \app\admin\model\yexam\Exam::refresh_question($exam_id);
        
        $this->success();

    }


    /**
     * 考试抽取题目
     */
    public function add_exam_ques(){
        $exam_id = $this->request->post("exam_id");
        $ids= $this->request->post("ids/a");
        $exam = new \app\admin\model\yexam\Exam();
        $examInfo = $exam->where(['id'=>$exam_id])->find();


        $ques_ids = array_unique(array_merge($ids,explode(",",$examInfo['question_ids'])));

        $exam->where(['id'=>$exam_id])->update(['question_ids'=>empty($ques_ids)?0:implode(",",$ques_ids)]);

        if(!empty($examInfo)){
            $examInfo = $exam->where(['id'=>$exam_id])->find();
            $quesionModel = new \app\admin\model\yexam\Question();
            $quesCount = $quesionModel->where(['id'=>['in',$examInfo['question_ids']]])->count();
            $configModel = new \app\admin\model\yexam\ExamConfig();
            $config = $configModel->where(array('exam_id'=> $exam_id))->find();
            $score = $config->score*$quesCount;

            $exam->where(array('id'=>$exam_id))->update(array('num'=>$quesCount,'score'=>$score));
        }

        \app\admin\model\yexam\Exam::refresh_question($exam_id);
        $this->success();
    }

    /**
     * 删除
     */
    public function del_exam_ques()
    {
        $exam_id = $this->request->post("exam_id");
        $ids= $this->request->post("ids/a");

        if ($ids) {

            $exam = new \app\admin\model\yexam\Exam();
            $examInfo = $exam->where(array('id'=>$exam_id))->find();
            $question_ids_arr = explode(",",$examInfo['question_ids']);

            foreach($ids as $id){
                if(!in_array($id,$question_ids_arr)){
                    $this->error('删除失败');exit;
                }
                if(empty($id)){
                    $this->error('参数错误');exit;
                }

                $user = new \app\admin\model\yexam\Question();
                $row = $user->where(['id'=>$id])->find();

                if($row){
                    if($row['unit_id'] == 0){
                        $row->delete();
                    }

                    foreach($question_ids_arr as $k=>$v){
                        if($id == $v){
                            unset($question_ids_arr[$k]);
                        }
                    }
                }

            }

            //更新考试题目
            $user = new \app\admin\model\yexam\Question();
            $ids = $user->where(['id'=>['in',$question_ids_arr]])->column('id');

            $examInfo->save(['question_ids'=>implode(",",$ids)]);

            \app\admin\model\yexam\Exam::refresh_question($exam_id);

            $this->success();
        }
        $this->error(__('No rows were deleted', 'ids'));
    }

    /**
     * 考试添加题目
     */
    public function addquestion()
    {
        $exam_id = $this->request->get("exam_id");

        $exam = new \app\admin\model\yexam\Exam();
        $examInfo = $exam->where(array('id'=>$exam_id))->find();
        $this->assign('examInfo',$examInfo);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $exam_id = $params['exam_id'];
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {

                    $type = $params['type'];
                    switch ($type){
                        case "1":
                            $params['right_answer'] = $params['right_answer_danxuan'];
                            break;
                        case "2":
                            $duoxuan = $this->request->post("right_answer_duoxuan/a");
                            $params['right_answer'] = implode("",$duoxuan);

                            break;
                        case "3":
                            $params['right_answer'] = $params['right_answer_panduan'];
                            break;
                    }
                    $questionModel = new \app\admin\model\yexam\Question();
                    $result = $questionModel->allowField(true)->save($params);
                    if ($result !== false) {
                        if($type != 3){

                            foreach($params['answer'] as $k=>$v){
                                if(empty($v)){
                                    continue;
                                }
                                $answerModel = new Answer();
                                $answerModel->data(array('question_id'=>$questionModel->id,'answer_code'=>$k,'answer'=>$v))->save();
                            }

                        }

                        $question_ids_arr = empty($examInfo->question_ids)?[]:explode(",",$examInfo->question_ids);
                        array_push($question_ids_arr,$questionModel->id);

                        $examInfo->save(['question_ids'=>implode(",",$question_ids_arr)]);
                        \app\admin\model\yexam\Exam::refresh_question($exam_id);

                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $questionModel = new \app\admin\model\yexam\Question;
        $this->view->assign("typeList", $questionModel->getTypeList());


        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function editquestion($ids = NULL)
    {
        $model = new \app\admin\model\yexam\Question();
        $row = $model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }

                    $type = $params['type'];
                    switch ($type){
                        case "1":
                            $params['right_answer'] = $params['right_answer_danxuan'];
                            break;
                        case "2":
                            $duoxuan = $this->request->post("right_answer_duoxuan/a");
                            $params['right_answer'] = implode("",$duoxuan);

                            break;
                        case "3":
                            $params['right_answer'] = $params['right_answer_panduan'];
                            break;
                    }

                    $result = $row->allowField(true)->save($params);

                    if ($result !== false) {
                        if($type != 3){
                            $answerModel = new Answer();
                            $answerModel->where(array('question_id'=>$row->id))->delete();
                            foreach($params['answer'] as $k=>$v){
                                if(empty($v)){
                                    continue;
                                }
                                $answerModel = new Answer();
                                $answerModel->data(array('question_id'=>$row->id,'answer_code'=>$k,'answer'=>$v))->save();
                            }

                        }

                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }


                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $answerModel = new Answer();
        $answersList = collection($answerModel->where(array('question_id'=>$row['id']))->select())->toArray();
        $answers = [];
        foreach($answersList as $k=>$v){
            $answers["{$v['answer_code']}"] = $v['answer'];
        }
        $questionModel = new \app\admin\model\yexam\Question;
        $this->view->assign("typeList", $questionModel->getTypeList());

        if($row['type'] == 2){
            $row['right_answer'] = Util::str_split_unicode($row['right_answer'],1);
        }
        $this->view->assign("answers", $answers);
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }



    /**
     * 抽题
     */
    public  function sel_question(){
        $exam_id = $this->request->get("exam_id");
        $exam = new  \app\admin\model\yexam\Exam();
        $examInfo =$exam->where(array('id'=>$exam_id))->find();
        $this->assign('examInfo',$examInfo);

        $unitModel = new \app\admin\model\yexam\Unit();

        $chanelList = [];
        $unitList = collection($unitModel
            ->where(array('subject_id'=>$examInfo['subject_id']))
            ->order("sort","asc")
            ->select())->toArray();

        foreach($unitList as $v){
            $chanelList[$v['id']] = ['id'=>$v['id'],'text'=>$v['unit_name'],'num'=>0,'parent'=>empty($v['pid'])?'#':$v['pid'],"type"=>"list",'state'=>['opened'=>false]];
        }

        /**
         * 处理章节数量
         */
        $model = new  \app\admin\model\yexam\Question();

        $questionList = collection($model
            ->field("count(id) as num,unit_id")
            ->where("id",'in',$examInfo['question_ids'])
            ->group("unit_id")->select())->toArray();

        foreach($questionList as $k=>$v){
            if(empty($chanelList[$v['unit_id']]['parent'])){
                continue;
            }
            $chanelList[$v['unit_id']]['num'] = $v['num'];
            $parent = $chanelList[$v['unit_id']]['parent'];

            while($parent != '#'){
                $chanelList[$parent]['num'] += $v['num'] ;
                $parent = $chanelList[$parent]['parent'];
            }
        }

        $chanelList = array_values($chanelList);

        foreach($chanelList as $k=>$v){
            if($v['num'] == 0){
                continue;
            }
            $chanelList[$k]['text'] = $chanelList[$k]['text']." <small class='label bg-red'>".$v['num']."</small>";
        }

        $this->assignconfig("channelList", $chanelList);

        return $this->view->fetch();
    }


    /**
     * 更新树
     */
    public  function refresh_tree(){
        $exam_id = $this->request->post("exam_id");
        $exam = new \app\admin\model\yexam\Exam();
        $examInfo =$exam->where(array('id'=>$exam_id))->find();

        $unitModel = new \app\admin\model\yexam\Unit();

        $chanelList = [];
        $unitList = collection($unitModel
            ->where(array('subject_id'=>$examInfo['subject_id']))
            ->order("sort","asc")
            ->select())->toArray();

        foreach($unitList as $v){
            $chanelList[$v['id']] = ['id'=>$v['id'],'text'=>$v['unit_name'],'num'=>0,'parent'=>empty($v['pid'])?'#':$v['pid'],"type"=>"list",'state'=>['opened'=>false]];
        }

        /**
         * 处理章节数量
         */
        $model = new  \app\admin\model\yexam\Question();

        $questionList = collection($model
            ->field("count(id) as num,unit_id")
            ->where("id",'in',$examInfo['question_ids'])
            ->group("unit_id")->select())->toArray();

        foreach($questionList as $k=>$v){
            if(empty($chanelList[$v['unit_id']]['parent'])){
                continue;
            }
            $chanelList[$v['unit_id']]['num'] = $v['num'];
            $parent = $chanelList[$v['unit_id']]['parent'];

            while($parent != '#'){
                $chanelList[$parent]['num'] += $v['num'] ;
                $parent = $chanelList[$parent]['parent'];
            }
        }

        $chanelList = array_values($chanelList);

        foreach($chanelList as $k=>$v){
            if($v['num'] == 0){
                continue;
            }
            $chanelList[$k]['text'] = $chanelList[$k]['text']." <small class='label bg-red'>".$v['num']."</small>";
        }

        $this->assignconfig("channelList", $chanelList);

        echo json_encode(['code'=>1, 'chanelList'=>$chanelList]);
    }

    /**
     * 导入
     */
    public function import()
    {

        $exam_id = $this->request->request('exam_id');

        $exam = new \app\admin\model\yexam\Exam();
        $examInfo = $exam->where(array('id'=>$exam_id))->find();
        if(empty($examInfo)){
            $this->error('当前考试不存在！');
        }

        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;

        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }


        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        $reader = new Xlsx();

        //加载文件
        $insert = [];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行


            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }


            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
                $temp = array_combine($fields, $values);

                foreach ($temp as $k => $v) {
                    if (isset($k) && $k !== '') {
                        $row[$k] = $v;
                    }
                }

                if ($row) {
                    $insert[] = $row;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }


        $insert_question_id = [];
        try {
            $answer_code = array("答案A"=>'A',"答案B"=>'B',"答案C"=>'C',"答案D"=>'D');
            $typeList = array('单选题'=>1,'多选题'=>2,'判断题'=>3);

            foreach($insert as $k=>$v){

                $type_name = $v['题目类型'];

                $question_name = $v['题目'];
                $area = $v['解析'];
                $right_answer = $v['正确答案'];

                if(empty($question_name) || empty($right_answer)){
                    continue;
                }

                //添加题目
                $question = new \app\admin\model\yexam\Question();

                if($typeList[$type_name] == 3){
                    $right_answer = $right_answer=='对'?1:0;
                }

                $question_id = $question->insertGetId(array('question_name'=>trim($question_name),'subject_id'=>0,'unit_id'=>0,'type'=>$typeList[$type_name],'right_answer'=>$right_answer,'area'=>$area));

                //添加答案
                if($typeList[$type_name] == 1 || $typeList[$type_name] == 2){
                    foreach($answer_code as $kk=>$vv){
                        if(empty($v[$kk])){
                            continue;
                        }
                        $answerModel = new Answer();

                        $answer['question_id'] = $question_id;
                        $answer['answer_code'] = $vv;
                        $answer['answer'] = $v[$kk];

                        $answerModel->insert($answer);
                    }
                }

                array_push($insert_question_id,$question_id);
            }

            if($insert_question_id){
                $question_ids_arr = empty($examInfo->question_ids)?[]:explode(",",$examInfo->question_ids);
                $ids = array_merge($question_ids_arr,$insert_question_id);

                $examInfo->save(['question_ids'=>implode(",",$ids)]);
                \app\admin\model\yexam\Exam::refresh_question($exam_id);
            }


        } catch (\think\exception\PDOException $exception) {
            $this->error($exception->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }


    /**
     * 导出pdf
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pdf_all(){

        $id = $this->request->post('exam_id');
        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $examUserModel = new ExamUser();
        $examModel = new \app\admin\model\yexam\Exam();

        $examInfo = $examModel->where(['id'=>$id])->find();
        $path =  str_replace('\\','/',realpath(dirname(__FILE__).'/../../../../'))."/public/file/exam/".$examInfo['id']."/";
        $zip_path =  str_replace('\\','/',realpath(dirname(__FILE__).'/../../../../'))."/public/file/exam/zip";
        $count = $examUserModel->where(['exam_id'=>$id])->count();
        $examUserList = $examUserModel->where(['exam_id'=>$id,'up_status'=>2])->order("score desc")->page($page,$limit)->select();

        foreach($examUserList as $key=>$item){
            $examuserlog = new ExamUserLog();
            $question_ids = $examInfo['question_ids'];

            $userModel = new User();
            $userInfo = $userModel->where(['id'=>$item['user_id']])->find();

            $question = new \app\admin\model\yexam\Question();

            $type = array("1"=>'单选','2'=>'多选','3'=>'判断');

            $data = collection($question->where('id','in',$question_ids)->select())->toArray();
            foreach($data as $k=>$v){
                $log = $examuserlog->where(['exam_user_id'=>$item['id'],'question_id'=>$v['id']])->find();

                $data[$k]['type_name'] = $type[$v['type']];

                if($v['type'] == 3){
                    $data[$k]['right_answe_true'] = $v['right_answer'];
                    $data[$k]['right_answer'] = $v['right_answer'] == 1?'对':'错';
                    $data[$k]['user_answer'] = $log['user_answer'] == 1?'对':'错';
                    $data[$k]['answers'] = [];
                }else{
                    $answer = new Answer();
                    $answers = collection($answer->where(array('question_id'=>$v['id']))->select())->toArray();
                    $data[$k]['user_answer'] = $log['user_answer'];
                    $data[$k]['answers'] = $answers;
                }
            }

            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // 设置文档信息
            $pdf->SetCreator('Helloweba');
            $pdf->SetAuthor('yueguangguang');
            $pdf->SetTitle($examInfo['exam_name']."—".(empty($userInfo['nickname'])?$userInfo['username']:$userInfo['nickname']));
            $pdf->SetHeaderData('', 0, '', "XXX考试系统", array(0,0,0), array(0,0,0));
            $pdf->setFooterData(array(0,0,0), array(0,0,0));
            $pdf->setHeaderFont(Array('stsongstdlight', '', '12'));
            $pdf->setFooterFont(Array('helvetica', '', '8'));
            $pdf->SetDefaultMonospacedFont('courier');
            //$pdf->SetMargins(15, 20, 15);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 25);
            $pdf->setImageScale(1.25);
            $pdf->setFontSubsetting(true);
            $pdf->AddPage();

            //输出考试名称
            $html = "<table ><tr><td>".$examInfo['exam_name']."</td></tr></table>";
            $pdf->SetFont('stsongstdlight', '',20 );
            $pdf->writeHTMLCell(0, 15, '', '', $html, 0, 1, 0, true, 'C', true);

            //输出开始基础信息
            $html = "<table><tr><td>学员姓名：".(empty($userInfo['nickname'])?$userInfo['nickname']:$userInfo['nickname'])."</td><td>得分：".$item['score']."</td><td>考试总分数：".$examInfo['score']."</td><td>考试时长：".$examInfo['givetime']."分钟</td></tr></table>";
            $pdf->SetFont('stsongstdlight', '',14 );
            $pdf->writeHTMLCell(0, 5, '', '', $html, 0, 1, 0, true, 'C', true);

            $html = "<table>";
            foreach($data as $k=>$v){
                $html.="<tr><td></td></tr><tr><td>".($k+1).".【".$v['type_name']."】".$v['question_name']."</td></tr>";
                $html .="<tr><td>";
                foreach($v['answers'] as $kk=>$vv){
                    $vv['answer'] = str_replace(">","大于",$vv['answer']);
                    $vv['answer'] = str_replace("<","小于",$vv['answer']);
                    $html.="&nbsp;&nbsp;&nbsp;&nbsp;".$vv['answer_code']."：".$vv['answer'];
                }
                $html .="</td></tr>";
                $html.="<tr><td>正确答案：".$v['right_answer']."&nbsp;&nbsp;&nbsp;学员答案：".$v['user_answer']."</td></tr>";

            }
            $html.="</table>";

            $pdf->SetFont('stsongstdlight', '',10 );
            $pdf->writeHTMLCell(250, 0, '', '', $html, 0, 1, 0, true, '', true);


            if(!file_exists($path)){
                mkdir($path,0777,true);
            }

            $pdf->Output($path.str_replace(" ","",$examInfo['exam_name']."_".(empty($userInfo['nickname'])?$userInfo['username']:$userInfo['nickname']))."_".$item['id'].".pdf", 'F');
        }


        if(!file_exists($zip_path)){
            mkdir($zip_path,0777,true);
        }

        $url = "";
        if(empty($examUserList)){
            if($page == 1){
                return json(array('code'=>1,'error'=>"暂无记录"));
            }

            $url = "/file/exam/zip/".$examInfo['id'].".zip";

            Makezip::zip($path,$zip_path."/".$examInfo['id'].".zip");
        }

        return json(array('code'=>0,'count'=>$count,'data'=>$examUserList,'url'=>$url));
    }


    public function pdf(){

        $id = $this->request->get('id');

        $examUserModel = new \app\admin\model\yexam\ExamUser();
        $examModel = new \app\admin\model\yexam\Exam();
        $examUser = $examUserModel->where(['id'=>$id])->find();

        $examInfo = $examModel->where(['id'=>$examUser['exam_id']])->find();
        $examuserlog = new \app\admin\model\yexam\ExamUserLog();
        $question_ids = $examInfo['question_ids'];

        $userModel = new User();
        $userInfo = $userModel->where(['id'=>$examUser['user_id']])->find();

        $question = new \app\admin\model\yexam\Question();

        $type = array("1"=>'单选','2'=>'多选','3'=>'判断');

        $data = collection($question->where('id','in',$question_ids)->select())->toArray();
        foreach($data as $k=>$v){
            $log = $examuserlog->where(['exam_user_id'=>$id,'question_id'=>$v['id']])->find();

            $data[$k]['type_name'] = $type[$v['type']];

            if($v['type'] == 3){
                $data[$k]['right_answe_true'] = $v['right_answer'];
                $data[$k]['right_answer'] = $v['right_answer'] == 1?'对':'错';
                $data[$k]['user_answer'] = $log['user_answer'] == 1?'对':'错';
                $data[$k]['answers'] = [];
            }else{
                $answer = new Answer();
                $answers = collection($answer->where(array('question_id'=>$v['id']))->select())->toArray();
                $data[$k]['user_answer'] = $log['user_answer'];
                $data[$k]['answers'] = $answers;
            }
        }

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle($examInfo['exam_name']."—".(empty($userInfo['nickname'])?$userInfo['username']:$userInfo['nickname']));
        $pdf->SetHeaderData('', 0, '', "考试系统", array(0,0,0), array(0,0,0));
        $pdf->setFooterData(array(0,0,0), array(0,0,0));
        $pdf->setHeaderFont(Array('stsongstdlight', '', '12'));
        $pdf->setFooterFont(Array('helvetica', '', '8'));
        $pdf->SetDefaultMonospacedFont('courier');
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->setImageScale(1.25);
        $pdf->setFontSubsetting(true);
        //设置字体
        $pdf->AddPage();

        //输出考试名称
        $html = "<table ><tr><td>".$examInfo['exam_name']."</td></tr></table>";
        $pdf->SetFont('stsongstdlight', '',20 );
        $pdf->writeHTMLCell(0, 15, '', '', $html, 0, 1, 0, true, 'C', true);

        //输出开始基础信息
        $html = "<table><tr><td>学员姓名：".(empty($userInfo['nickname'])?$userInfo['nickname']:$userInfo['nickname'])."</td><td>得分：".$examUser['score']."</td><td>考试总分数：".$examInfo['score']."</td><td>考试时长：".$examInfo['givetime']."分钟</td></tr></table>";
        $pdf->SetFont('stsongstdlight', '',14 );
        $pdf->writeHTMLCell(0, 5, '', '', $html, 0, 1, 0, true, 'C', true);

        $html = "<table style='width:100%;border: #0a6aa1 solid 2px;'>";
        foreach($data as $k=>$v){
            $html.="<tr><td></td></tr><tr><td>".($k+1).".【".$v['type_name']."】".$v['question_name']."</td></tr>";
            $html .="<tr><td>";
            foreach($v['answers'] as $kk=>$vv){
                $vv['answer'] = str_replace(">","大于",$vv['answer']);
                $vv['answer'] = str_replace("<","小于",$vv['answer']);
                $html.="&nbsp;&nbsp;&nbsp;&nbsp;".$vv['answer_code']."：".$vv['answer'];
            }
            $html .="</td></tr>";
            $html.="<tr><td>正确答案：".$v['right_answer']."&nbsp;&nbsp;&nbsp;学员答案：".$v['user_answer']."</td></tr>";

        }
        $html.="</table>";

        $pdf->SetFont('stsongstdlight', '',10 );
        $pdf->writeHTMLCell(250, 0, '', '', $html, 0, 1, 0, true, '', true);
        echo $pdf->Output($examInfo['exam_name']."—".(empty($userInfo['nickname'])?$userInfo['username']:$userInfo['nickname']).".pdf", 'D');exit;
    }

    /**
     * 查看
     */
    public function user_log()
    {
        //设置过滤方法
        $id = $this->request->get('exam_id');
        if(Request::instance()->isAjax()){

            $examuser = new \app\admin\model\yexam\ExamUser();
            $examUserLog = new \app\admin\model\yexam\ExamUserLog();
            $count = $examuser->where(['exam_id'=>$id,'up_status'=>2])->count();
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $lists = collection($examuser
                ->field("a.*,b.mobile,b.nickname,IFNULL(count(c.id),0)  as error_num")
                ->alias("a")
                ->join("user b",'a.user_id = b.id','left')
                ->join("yexam_exam_user_log c",'a.id = c.exam_user_id and c.state=0','left')
                ->group("a.id")
                ->where(['a.exam_id'=>$id,'a.up_status'=>2])
                ->order("a.score desc")
                ->limit($offset, $limit)
                ->select())->toArray();

            foreach($lists as $k=>$v){
                $answer_num = $examUserLog->where(['exam_user_id'=>$v['id']])->count();
                $lists[$k]['answer_num'] = $answer_num;     //答题数

                $right_num = $examUserLog->where(['exam_user_id'=>$v['id'],'state'=>1])->count();
                $lists[$k]['right_num'] = $right_num;     //答题正确数
                $lists[$k]['pm'] = intval($offset+$k+1);
            }
            return json(array('total'=>$count,'rows'=>$lists));
        }
        $this->assign('id',$id);
        return $this->view->fetch();
    }

    /**
     *
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function error_user_log(){

        $id = $this->request->get('id');
        if(Request::instance()->isAjax()){

            $examuserlog = new Examuserlog();


            $data = $examuserlog
                ->field("group_concat(question_id) as question_ids")
                ->where(['exam_user_id'=>$id,'state'=>0])->find();
            $question_ids = empty($data->question_ids)?0:$data->question_ids;

            $question = new \app\admin\model\yexam\Question();
            $count = $question->where('id','in',$question_ids)->count();
            $type = array("1"=>'单选','2'=>'多选','3'=>'判断');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $data = collection($question->where('id','in',$question_ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select())->toArray();
            foreach($data as $k=>$v){
                $log = $examuserlog->where(['exam_user_id'=>$id,'state'=>0,'question_id'=>$v['id']])->find();

                $data[$k]['type_name'] = $type[$v['type']];

                if($v['type'] == 3){
                    $data[$k]['right_answe_true'] = $v['right_answer'];
                    $data[$k]['right_answer'] = $v['right_answer'] == 1?'对':'错';
                    $data[$k]['user_answer'] = $log['user_answer'] == 1?'对':'错';

                }else{
                    $answer = new Answer();
                    $answers = collection($answer->where(array('question_id'=>$v['id']))->select())->toArray();
                    $data[$k]['user_answer'] = $log['user_answer'];
                    $data[$k]['answers'] = $answers;
                }
            }

            return json(array('code'=>0,'total'=>$count,'rows'=>$data));
        }

        $this->assign('id',$id);
        return $this->fetch();
    }


}

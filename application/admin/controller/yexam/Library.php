<?php

namespace app\admin\controller\yexam;


use addons\yexam\library\Util;
use app\admin\model\yexam\Answer;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Exception;

/**
 * 题目管理
 *
 * @icon fa fa-question
 */
class Library extends Backend
{

    protected $model = null;
    protected $searchFields = 'id,question_name';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yexam\Library();

        $subjectModel = new \app\admin\model\yexam\Subject();
        $subjectList = $subjectModel->where(['status'=>1])->select();

        $this->view->assign('subjectList', $subjectList);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('subject')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with('subject')
                ->where($where)

                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 题目列表
     */
    public function question()
    {
        $library_id = $this->request->get("library_id");

        $libraryInfo = $this->model->where(array('id'=>$library_id))->find();
        $this->assign('libraryInfo',$libraryInfo);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $model = new \app\admin\model\yexam\Question();
            $question_ids = $libraryInfo['question_ids'];

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
     * 添加题目
     */
    public function addquestion()
    {
        $library_id = $this->request->get("library_id");

        $libraryInfo = $this->model->where(array('id'=>$library_id))->find();
        $this->assign('libraryInfo',$libraryInfo);

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params['subject_id'] = $libraryInfo['subject_id'];
            $params['library_id'] = $library_id;
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

                        $question_ids = empty($libraryInfo['question_ids'])?[]:explode(",",$libraryInfo['question_ids']);
                        array_push($question_ids,$questionModel->id);
                        $libraryInfo->save(['question_ids'=>implode(",",$question_ids)]);
                        \app\admin\model\yexam\Library::refresh_num($library_id);
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
     * 删除操作
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delquestion(){
        $ids = $this->request->post('ids');
        $library_id = $this->request->get('library_id');

        $libraryInfo = $this->model->where(array('id'=>$library_id))->find();
        $question_ids_arr = empty($libraryInfo['question_ids'])?[]:explode(",",$libraryInfo['question_ids']);

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

        //更新题库题目
        $user = new \app\admin\model\yexam\Question();
        $ids = $user->where(['id'=>['in',$question_ids_arr]])->column('id');

        $libraryInfo->save(['question_ids'=>implode(",",$ids)]);
        \app\admin\model\yexam\Library::refresh_num($library_id);
        $this->success();

    }

    /**
     * 导入
     */
    public function import()
    {

        $library_id = $this->request->request('library_id');
        $libraryInfo = $this->model->where(array('id'=>$library_id))->find();
        if(empty($libraryInfo)){
            $this->error('当前题库不存在！');
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

                $question_id = $question->insertGetId(array('question_name'=>trim($question_name),'subject_id'=>$libraryInfo['subject_id'],'unit_id'=>0,'library_id'=>$library_id,'type'=>$typeList[$type_name],'right_answer'=>$right_answer,'area'=>$area));

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
                $question_ids_arr = empty($libraryInfo->question_ids)?[]:explode(",",$libraryInfo->question_ids);
                $ids = array_merge($question_ids_arr,$insert_question_id);

                $libraryInfo->save(['question_ids'=>implode(",",$ids)]);
                \app\admin\model\yexam\Library::refresh_num($library_id);
            }



        } catch (\think\exception\PDOException $exception) {
            $this->error($exception->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }


}

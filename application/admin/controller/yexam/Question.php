<?php

namespace app\admin\controller\yexam;

use addons\yexam\library\Util;
use app\admin\model\yexam\Answer;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Exception;

/**
 * 题目管理
 *
 * @icon fa fa-question
 */
class Question extends Backend
{

    protected $model = null;
    protected $searchFields = 'id,question_name';
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yexam\Question;
        $this->view->assign("typeList", $this->model->getTypeList());


        $unit_id = $this->request->param("unit_id",0);
        $model = new \app\admin\model\yexam\Unit();
        $query = $model->where(["id"=>$unit_id])->find();
        $unit = empty($query)?[]:$query->toArray();

        $this->assign('unit',$unit);
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

            $unit_id = $this->request->param('unit_id');

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where(['unit_id'=>$unit_id])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where(['unit_id'=>$unit_id])
                ->where($where)
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

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

                    $num = $this->model->where(array('subject_id'=>$params['subject_id']))->count();
                    $params['sub_num_id'] = $num+1;
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false) {
                        if($type != 3){

                            foreach($params['answer'] as $k=>$v){
                                if(empty($v)){
                                    continue;
                                }
                                $answerModel = new Answer();
                                $answerModel->data(array('question_id'=>$this->model->id,'answer_code'=>$k,'answer'=>$v))->save();
                            }
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


        $model = new \app\admin\model\yexam\Unit();
        $unit = $model->where(["id"=>$row['unit_id']])->find()->toArray();

        $this->assign('unit',$unit);
        $answerModel = new Answer();
        $answersList = collection($answerModel->where(array('question_id'=>$row['id']))->select())->toArray();
        $answers = [];
        foreach($answersList as $k=>$v){
            $answers["{$v['answer_code']}"] = $v['answer'];
        }
        if($row['type'] == 2){
            $row['right_answer'] = Util::str_split_unicode($row['right_answer'],1);
        }
        $this->view->assign("answers", $answers);
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }

    /**
     * 导入
     */
    public function import()
    {
        $unit_id = $this->request->request('unit_id');

        $unitModel = new \app\admin\model\yexam\Unit();
        $unitInfo = $unitModel->where(['id'=>$unit_id])->find();
        if (!$unitInfo) {
            $this->error(__('当前章节不存在'));
        }
        $file = $this->request->request('file');
        $arr = parse_url($file);
        $file = $arr['path'];
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


        try {
            $answer_code = array("答案A"=>'A',"答案B"=>'B',"答案C"=>'C',"答案D"=>'D');

            $typeList = array('单选题'=>1,'多选题'=>2,'判断题'=>3);
            foreach($insert as $k=>$v){
                $type_name = $v['题目类型'];

                $question_name = $v['题目'];
                $area = $v['解析'];
                $right_answer = $v['正确答案'];

                if(empty($question_name)){
                    continue;
                }
                if(empty($question_name) || empty($right_answer)){
                    throw new Exception('题目异常，请稍后再试');
                }

                $questionInfo = $this->model->where(array('question_name'=>$question_name,'type'=>$typeList[$type_name],'unit_id'=>$unit_id))->find();

                if(empty($questionInfo)){
                    if($typeList[$type_name] == 3){
                        $right_answer = $right_answer=='对'?1:0;
                    }

                    $question_id = $this->model->insertGetId(array('question_name'=>$question_name, 'subject_id'=>$unitInfo['subject_id'],'unit_id'=>$unit_id,'type'=>$typeList[$type_name],'right_answer'=>$right_answer,'area'=>$area));


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
                }
            }


        } catch (\think\exception\PDOException $exception) {
            $this->error($exception->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }



        $this->success();
    }




}

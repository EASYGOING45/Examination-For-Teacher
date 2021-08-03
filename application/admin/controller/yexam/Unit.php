<?php

namespace app\admin\controller\yexam;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 章节管理
 *
 * @icon fa fa-circle-o
 */
class Unit extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yexam\Unit;

    }

    /**
     * 添加章节
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $subject_id = $this->request->param('subject_id');
        $tree = Tree::instance();
        $tree->init(collection($this->model->where(array('subject_id'=>$subject_id))->order('sort asc')->select())->toArray(), 'pid');
        $unitList = $tree->getTreeList($tree->getTreeArray(0), 'unit_name');
        $unitdata = [];
        foreach ($unitList as $k => $v)
        {
            $unitdata[$v['id']] = $v;
        }

        $this->assign('subject_id',$subject_id);
        $this->view->assign("parentList", $unitdata);

        return $this->fetch();


    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $subject_id = $this->request->param('subject_id');
        $tree = Tree::instance();
        $tree->init(collection($this->model->where(array('subject_id'=>$row['subject_id'],'id'=>['neq',$row['id']]))->order('sort asc')->select())->toArray(), 'pid');
        $categorylist = $tree->getTreeList($tree->getTreeArray(0), 'unit_name');
        $categorydata = [];
        foreach ($categorylist as $k => $v)
        {
            $categorydata[$v['id']] = $v;
        }

        $this->assign('subject_id',$subject_id);
        $this->view->assign("parentList", $categorydata);

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 查看章节列表
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            $subject_id = $this->request->param('subject_id');
            $tree = Tree::instance();
            $tree->init(collection($this->model->where(array('subject_id'=>$subject_id))->order('sort asc')->select())->toArray(), 'pid');
            $unitList = $tree->getTreeList($tree->getTreeArray(0), 'unit_name');


            $total = count($unitList);
            $result = array("total" => $total, "rows" => $unitList);

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
     * 删除章节
     */
    public function del($ids = null)
    {

        if ($ids) {

            $row = $this->model->get($ids);
            if (!$row) {
                $this->error(__('No Results were found'));
            }

            //判断是否有下级章节
            $childRow = $this->model->get(['pid'=>$ids]);
            if ($childRow) {
                $this->error(__('请先删除下级章节'));
            }
            if ($row->delete()) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));

    }
}

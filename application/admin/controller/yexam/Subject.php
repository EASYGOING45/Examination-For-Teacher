<?php

namespace app\admin\controller\yexam;

use app\common\controller\Backend;
use fast\Tree;

/**
 * 科目管理
 *
 * @icon fa fa-circle-o
 */
class Subject extends Backend
{

    protected $noNeedRight = [''];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\yexam\Subject;

    }

}

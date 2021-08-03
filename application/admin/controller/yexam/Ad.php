<?php

namespace app\admin\controller\yexam;

use app\common\controller\Backend;

/**
 * 轮播图
 *
 * @icon fa fa-circle-o
 */
class Ad extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new  \app\admin\model\yexam\Ad();

    }
}

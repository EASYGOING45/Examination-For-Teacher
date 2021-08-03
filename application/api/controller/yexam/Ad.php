<?php

namespace app\api\controller\yexam;

use app\common\controller\Api;

/**
 * Ad接口
 */
class Ad extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['index'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    /**
     * 获取轮播图列表
     */
    public function index()
    {
        $page = $this->request->post('page');
        $limit = $this->request->post('limit');

        $subject = new \addons\yexam\service\Ad();
        $data = $subject->getAdList($page,$limit);
        foreach($data['data'] as &$v){
            $v['image'] = $this->request->domain().$v['image'];
        }
        $this->success('请求成功', $data);
    }

}

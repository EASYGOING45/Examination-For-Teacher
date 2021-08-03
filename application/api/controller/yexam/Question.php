<?php

namespace app\api\controller\yexam;

use app\common\controller\Api;

/**
 * 题库接口
 */
class Question extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    public function info(){
        $id = $this->request->post('id');
        $question = new \addons\yexam\service\Question();
        $data = $question->getQuestion($id,$this->auth->id);

        $this->success('请求成功',$data);
    }

}

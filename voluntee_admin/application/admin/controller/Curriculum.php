<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think\Db;

class Curriculum extends BasicAdmin2
{

    private $agent_id;
    public function _initialize($value='')
    {
        $user = session('user');
        $this->agent_id = $user['agent_id'];
    }
    
    public function index(){
        $this->assign([
            'title' => '活动管理'
        ]);
        return $this->view->fetch('', [
            'list' => [],
            'title' => '课程管理'
        ]);
        // return $this->viewPaginated($db);
    }
    
}
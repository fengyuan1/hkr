<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think\Db;

class Level extends BasicAdmin2
{

    private $agent_id;
    public $table = 'level_type';
    public function _initialize($value='')
    {
        $user = session('user');
        $this->agent_id = $user['agent_id'];
    }
    
    public function index()
    {
        $db = Db::table($this->table)
            ->field([
                'id',
                'level_name' => 'name',
                'integration'
            ]);
        $this->assign([
            'title' => '义工列表'
        ]);
        return $this->viewPaginated($db);
    }

    public function add()
    {
        if($this->request->isGet()) {
            return $this->view->fetch();
        }
        if($this->request->isPost()) {
            $list = [
                'level_name' => input('name', ''),
                'integration' => input('integration', 0)
            ];
            if(empty($list['level_name']) || empty($list['integration'])) {
                $this->error('请填写数据');
            }
            $isHas = Db::table($this->table)->where('level_name', $list['level_name'])->count();
            if($isHas) {
                $this->error('等级名称不能相同，请另起等级名称');
            }
            $isIntegration = Db::table($this->table)->where('integration', $list['integration'])->count();
            if($isIntegration) {
                $this->error('等级所需积分不能相同，请另设置等级所需积分');
            }
            $isAdd = Db::table($this->table)->insert($list);
            if($isAdd) {
                $this->success('添加成功', '');
            }
            $this->error('添加失败，请重试');
        }
    }

    public function edit()
    {
        if ($this->request->isGet()) {
            $params = [
                'level_id' => input('id/d', null)
            ];
            $data = Db::table($this->table)
                ->where('id', $params['level_id'])
                ->field([
                    'id',
                    'level_name',
                    'integration'
                ])
                ->find();
            return $this->view->fetch('', [
                'data' => $data
            ]);
        }
        
        if($this->request->isPost()) {
            $params = [
                'level_id' => input('id/d', null)
            ];
            $list = [
                'level_name' => input('name', ''),
                'integration' => input('integration', 0)
            ];
            $isHas = Db::table($this->table)
                ->whereNotIn('id', $params['level_id'])
                ->where('level_name', $list['level_name'])
                ->count();
            if($isHas) {
                $this->error('等级名称不能相同，请另起等级名称');
            }
            $isIntegration = Db::table($this->table)
                ->whereNotIn('id', $params['level_id'])
                ->where('integration', $list['integration'])
                ->count();
            if($isIntegration) {
                $this->error('等级所需积分不能相同，请另设置等级所需积分');
            }
            $isEdit = Db::table($this->table)
                ->where('id', $params['level_id'])
                ->update($list);
            if($isEdit) {
                $this->success('修改成功', '');
            }
            $this->error('修改失败，请重试', '');
        }
    }
    
    public function del()
    {
        $params = [
            'user_id' => input('id', null)
        ];
        $isDel = Db::table('user')->where('id', $params['user_id'])->update(['is_check' => 0]);
        if($isDel) {
            $this->success('义工删除成功', '');
        }
        $this->error('义工删除失败，请重试。', '');
    }
}
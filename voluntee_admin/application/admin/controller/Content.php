<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think\Db;
use library;

class Content extends BasicAdmin2
{

    private $agent_id;
    public $table = 'help_list';
    public function _initialize($value='')
    {
        $user = session('user');
        $this->agent_id = $user['agent_id'];
    }
    
    public function index(){
        $db = Db::table($this->table)
            ->field([
                'id',
                'title',
                'sort',
                'status'
            ])
            ->order([
                'sort' => 'asc',
                'create_time'
            ]);
        $db = (new library\DbPaginator($db))->each(function ($item) {
            $item['statusMsg'] = $item['status'] === 0 ? '上架中' : '下架';
            return $item;
        });
        $this->assign([
            'title' => '帮助列表'
        ]);
        return $this->viewPaginated($db);
    }
    
    public function add()
    {
        if($this->request->isGet()) {
            return $this->view->fetch('', [
                'title' => '帮助列表 -> 添加'
            ]);
        }
        if($this->request->isPost()) {
            $list = [
                'title' => input('title', ''),
                'sort' => input('sort/d', 1),
                'status' => input('status/d', 0)
            ];
            $detail = [
                'content' => input('content', '')
            ];
            Db::startTrans();
            try {
                $help_id = Db::table($this->table)->insertGetId($list);
                $detail['help_id'] = $help_id;
                Db::table('help_detail')->insert($detail);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $this->error('添加失败，请重试');
                Db::rollback();
            }
            $this->success('添加成功', gen_url('index'));
        }
    }

    public function edit()
    {
        if($this->request->isGet()) {
            $params = [
                'help_id' => input('id/d', null)
            ];
            $data = Db::table($this->table)
                ->alias('help')
                ->join(['help_detail' => 'detail'], 'detail.help_id = help.id')
                ->where('help.id', $params['help_id'])
                ->field([
                    'help.id' => 'id',
                    'help.title' => 'title',
                    'help.sort' => 'sort',
                    'help.status' => 'status',
                    'detail.content' => 'content'
                ])
                ->find();
            return $this->view->fetch('', [
                'title' => '帮助列表 -> 编辑',
                'data' => $data
            ]);
        }
        if($this->request->isPost()) {
            $params = [
                'help_id' => input('id/d', null)
            ];
            $list = [
                'title' => input('title', ''),
                'status' => input('status/d', 0),
                'sort' => input('sort/d', 1),
            ];
            $detail = [
                'content' => input('content', '')
            ];
            Db::startTrans();
            try {
                Db::table($this->table)->where('id', $params['help_id'])->update($list);
                Db::table('help_detail')->where('help_id', $params['help_id'])->update($detail);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $this->error('内容编辑失败，请重试');
                Db::rollback();
            }
            $this->success('编辑成功', gen_url('index'));
        }
    }

    public function del()
    {
        $params = [
            'help_id' => input('id/d', null)
        ];
        Db::startTrans();
        try {
            Db::table($this->table)->where('id', $params['help_id'])->delete();
            Db::table('help_detail')->where('help_id', $params['help_id'])->delete();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $this->error('删除失败，请重试');
            Db::rollback();
        }
        $this->success('编辑成功', '');
    }

    public function status()
    {
        $params = [
            'help_id' => input('id/d', null),
            'value' => input('value/d', 0)
        ];
        $isStatus = Db::table($this->table)
            ->where('id', $params['help_id'])
            ->update(['status' => $params['value']]);
        if($isStatus) {
            $this->success('编辑成功', '');
        }
        $this->error('编辑失败，请重试');
    }
}
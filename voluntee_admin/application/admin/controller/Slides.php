<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think\Db;
use library;

class Slides extends BasicAdmin2
{

    public $table = 'banner';
    private $agent_id;
    public function _initialize($value='')
    {
        $user = session('user');
        $this->agent_id = $user['agent_id'];
    }
    
    public function index(){
        $db = Db::table($this->table);

        $this->assign([
            'title' => 'Banner列表'
        ]);
        $db = (new library\DbPaginator($db))->each(function ($item) {
            $item['statusMsg'] = $item['status'] === 0 ? '显示' : '隐藏';
            return $item;
        });
        return $this->viewPaginated($db);
    }
    
    public function add()
    {
        if($this->request->isGet()) {
            return $this->view->fetch('', [
                'title' => 'Banner列表 -> 添加'
            ]);
        }
        if($this->request->isPost()) {
            $params = [
                'bannerId' => input('id', null)
            ];
            $list = [
                'name' => input('name', ''),
                'image' => empty(input('img', '')) ? '' : remove_domain_pre(input('img')),
                'url' => input('url', ''),
                'status' => input('status/d', 0),
                'create_time' => date('Y-m-d h:i:m', time())
            ];
            $isAdd = Db::table('banner')->where('id', $params['bannerId'])->insert($list);
            if($isAdd) {
                $this->success('banner添加成功', gen_url('index'));
            }
            $this->error('添加失败，请重试', '');
        }
    }

    public function edit()
    {
        if($this->request->isGet()) {
            // $temp = Request()->domain();
            // halt($temp);
            $params = [
                'bannerId' => input('id', null)
            ];
            $data = Db::table('banner')->where('id', $params['bannerId'])->find();
            return $this->view->fetch('', [
                'title' => 'Banner列表 -> 编辑',
                'data' => $data
            ]);
        }
        if($this->request->isPost()) {
            $params = [
                'bannerId' => input('id', null)
            ];
            $list = [
                'name' => input('name', ''),
                'url' => input('url', ''),
                'image' => empty(input('img', '')) ? '' : remove_domain_pre(input('img')),
                'status' => input('status/d', 0),
                'create_time' => date('Y-m-d h:i:m', time())
            ];
            if(empty($list['image'])) {
                $this->error('图片不能为空', '');
            }
            $image = Db::table('banner')->where('id', $params['bannerId'])->value('image');
            $dbDir = preg_match('@/upload/([0-9a-zA-Z]+)/@', $image, $dbMatch); //数据库的dir
            $listDir = preg_match('@/upload/([0-9a-zA-Z]+)/@', $list['image'], $listMatch); //传图片的dir
            if($dbMatch !== $listMatch) {
                $count = Db::table('banner')->where('image', $image)->count();
                if($count == 1) {

                }
            }
            $isEdit = Db::table('banner')->where('id', $params['bannerId'])->update($list);
            if($isEdit) {
                $this->success('banner编辑成功', gen_url('index'));
            }
            $this->error('编辑失败，请重试', '');
        }
    }

    public function status()
    {
        $params = [
            'bannerId' => input('id/d', null),
            'status' => input('value/d', 0)
        ];
        $isStatus = Db::table($this->table)
            ->where('id', $params['bannerId'])
            ->update(['status' => $params['status']]);
        if($isStatus) {
            $this->success('操作成功', '');
        }
        $this->error('操作失败', '');
    }

    public function del()
    {
        $params = [
            'bannerId' => input('id/d', null)
        ];
        $isDel = Db::table($this->table)
            ->where('id', $params['bannerId'])
            ->delete();
        if($isDel) {
            $this->success('删除成功', '');
        }
        $this->error('删除失败', '');
    }
}
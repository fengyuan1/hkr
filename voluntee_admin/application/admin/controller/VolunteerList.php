<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think\Db;
use library;

class VolunteerList extends BasicAdmin2
{

    private $agent_id;
    public $table = 'user';
    public function _initialize($value='')
    {
        $user = session('user');
        $this->user = $user;
    }
    
    public function index()
    {
        $db = Db::table($this->table)
            ->alias('user')
            ->join(['vol_review_record' => 'vrr'], 'vrr.userid = user.id', 'left')
            ->where('user.is_check', 1)
            ->field([
                'user.id' => 'id',
                'user.truename' => 'truename',
                'user.sex' => 'sex',
                'user.idcard' => 'idcard',
                'user.phone' => 'phone',
                'user.emergency_man' => 'emergency_man',
                'user.emergency_phone' => 'emergency_phone',
                'vrr.reviewer_name' => 'reviewer_name',
                'from_unixtime(vrr.checktime, "%Y-%m-%d %H:%i:%S")' => 'checktime',
                'user.level_id' => 'level'
            ]);
        $db = (new library\DbPaginator($db))->each(function ($item) {
            $item['sex'] = $item['sex'] === 0 ? '男' : '女';
            return $item;
        });
        $this->assign([
            'title' => '义工列表'
        ]);
        return $this->viewPaginated($db);
    }

    public function edit()
    {
        if ($this->request->isGet()) {
            $params = [
                'user_id' => input('id', null)
            ];
            $data = Db::table($this->table)->where('id', $params['user_id'])->find();
            $this->assign([
                'title' => '义工列表 -> 编辑',
                'data' => $data
            ]);
            return $this->view->fetch();
        }
        
        if($this->request->isPost()) {
            $params = [
                'user_id' => input('id', null)
            ];
            $list = [
                'truename' => input('name', ''),
                'sex' => input('sex/d', 0),
                'idcard' => input('idcard', ''),
                'phone' => input('phone', ''),
                'emergency_man' => input('emergency_man', ''),
                'emergency_phone' => input('emergency_phone', '')
            ];
            $isEdit = Db::table($this->table)
                ->where('id', $params['user_id'])
                ->update($list);
            if($isEdit) {
                $this->success('编辑成功', gen_url('index'));
            }
            $this->error('编辑失败，请重试', '');
        }
    }
    
    public function del()
    {
        $params = [
            'user_id' => input('id', null)
        ];
        $isDel = Db::table($this->table)->where('id', $params['user_id'])->update(['is_check' => 0]);
        if($isDel) {
            $this->success('义工删除成功', '');
        }
        $this->error('义工删除失败，请重试。', '');
    }

    public function audit()
    {
        $params = [
            'search' => input('search', ''),
            'is_check' => input('is_check/d', 'all')
        ];
        $db = Db::table('vol_review_record')
            ->alias('record')
            ->join(['user' => 'user'], 'user.id = record.userid')
            ->field([
                'record.id' => 'id',
                'user.truename' => 'truename',
                'user.nickname' => 'nickname',
                'user.sex' => 'sex',
                'user.idcard' => 'idcard',
                'user.phone' => 'phone',
                'user.emergency_man' => 'emergency_man',
                'user.emergency_phone' => 'emergency_phone',
                'user.is_check' => 'is_check',
                'from_unixtime(record.checktime, "%Y-%m-%d %H:%i:%S")' => 'check_time'
            ])
            ->order('record.create_time');
        if(!empty($params)) {
            $db->where('user.truename|user.nickname', 'like', '%'.$params['search'].'%');
        }
        if(($params['is_check'] === 0) || ($params['is_check'] === 1) || ($params['is_check'] === -1)) {
            $db->where('user.is_check', $params['is_check']);
        }
        $select = [
            [
                'value' => 'all',
                'name'  =>  '全部'
            ],
            [
                'value' => '1',
                'name'  =>  '通过'
            ],
            [
                'value' => '-1',
                'name'  =>  '未通过'
            ],
            [
                'value' => '0',
                'name'  =>  '未审核'
            ]
        ];
        $this->assign([
            'title' => '用户审核列表',
            'select' => $select
        ]);
        $db = (new library\DbPaginator($db))->each(function ($item) {
            $item['sex'] = $item['sex'] === 0 ? '男' : '女';
            return $item;
        });
        return $this->viewPaginated($db);
    }

    public function audit_detail()
    {
        $params = [
            'record_id' => input('id', null)
        ];
        if($this->request->isGet()) {
            $data = Db::table('vol_review_record')
                ->alias('record')
                ->join(['user' => 'user'], 'user.id = record.userid')
                ->where('record.id', $params['record_id'])
                ->field([
                    'record.id' => 'id',
                    'user.truename' => 'truename',
                    'user.nickname' => 'nickname',
                    'user.sex' => 'sex',
                    'user.idcard' => 'idcard',
                    'user.phone' => 'phone',
                    'user.emergency_man' => 'emergency_man',
                    'user.emergency_phone' => 'emergency_phone',
                    'user.is_check' => 'is_check',
                    'record.error_msg' => 'error_msg'
                ])
                ->find();
            return $this->view->fetch('', [
                'title' => '审核',
                'data' => $data
            ]);
        }
        if($this->request->isPost()) {
            $user = [
                'is_check' => input('is_check', 0)
            ];

            $record = [
                'reviewer_name' => $this->user['username'],
                'checktime' => time(),
                'error_msg' => input('error_msg', '')
            ];
            $user_id = Db::table('vol_review_record')->where('id', $params['record_id'])->value('userid');
            Db::startTrans();
            try {
                Db::table('vol_review_record')->where('id', $params['record_id'])->update($record);
                Db::table($this->table)->where('id', $user_id)->update($user);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $this->error('用户审核失败，请重试', '');
                Db::rollback();
            }
            $this->success('审核成功', gen_url('audit'));
        }
    }

    public function user()
    {
        $db = Db::table($this->table)
            ->whereNotIn('is_check', [1])
            ->field([
                'id',
                'nickname',
                'avatarUrl',
                'phone'
            ]);
        $this->assign([
            'title' => '用户列表'
        ]);
        // return $this->view->fetch('', ['list' => []]);
        return $this->viewPaginated($db);
    }
}
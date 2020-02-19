<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think\Db;
use library;

class Activity extends BasicAdmin2
{

    private $agent_id;
    public function _initialize($value='')
    {
        $user = session('user');
        $this->agent_id = $user['agent_id'];
    }
    
    public function index()
    {
        $params = [
            'status' => input('status/d', 'all'),
            'time' => input('time', null)
        ];
        $db = Db::table('activity')
            ->alias('activity')
            ->join(['activity_detail' => 'detail'], 'detail.activity_id = activity.id')
            ->join(['user' => 'user'], 'user.id = activity.area_user_id')
            ->join(['activity_type' => 'type'], 'type.id = activity.activity_type_id')
            ->field([
                'activity.id' => 'id',
                'activity.title' => 'activity_name',
                'activity.img' => 'img',
                'from_unixtime(detail.begin_time, "%Y-%m-%d %H:%i:%S")' => 'begin_time',
                'from_unixtime(detail.end_time, "%Y-%m-%d %H:%i:%S")' => 'end_time',
                'user.truename' => 'truename',
                'user.phone' => 'phone',
                'activity.create_time' => 'create_time',
                'activity.address' => 'address',
                'activity.status' => 'status',
                'type.name' => 'type_name'
            ])
            ->order('activity.create_time desc');
        if(($params['status'] === 0) || $params['status'] === 1) {
            $db->where('activity.status', $params['status']);
        }
        if(!empty($params['time'])) {
            list($start, $end) = library\DateRange::parse(input('time'));
            $db->whereTime('activity.create_time', 'between', [date('Y-m-d h:i:s', $start), date('Y-m-d h:i:s', $end)]);
        }
        
        $db = (new library\DbPaginator($db))->each(function ($item) {
            $item['status_msg'] = $item['status'] === 0 ? '上架中' : '下架';
            $item['img'] = joint_domain_pre($item['img']);
            return $item;
        });
        $select = [
            [
                'value' => 'all',
                'name'  =>  '全部'
            ],
            [
                'value' => '0',
                'name'  =>  '上架中'
            ],
            [
                'value' => '1',
                'name'  =>  '下架'
            ]
        ];
        $this->assign([
            'title' => '活动管理',
            'select' => $select
        ]);
        return $this->viewPaginated($db);
    }

    public function status()
    {
        $params = [
            'activity_id' => input('id', null),
            'value' => input('value/d', 0)
        ];
        $isStatus = Db::table('activity')
            ->where('id', $params['activity_id'])
            ->update(['status' => $params['value']]);
        if($isStatus) {
            $this->success('操作成功', '');
        }
        $this->error('操作失败，请重试', '');
    }

    public function edit()
    {
        if($this->request->isGet()) {
            $params = [
                'activity_id' => input('id', null)
            ];
            $data = Db::table('activity')
                ->alias('activity')
                ->join(['activity_detail' => 'detail'], 'detail.activity_id = activity.id')
                ->join(['user' => 'user'], 'user.id = activity.area_user_id')
                ->join(['activity_type' => 'type'], 'type.id = activity.activity_type_id')
                ->where('activity.id', $params['activity_id'])
                ->field([
                    'activity.id' => 'id',
                    'activity.title' => 'activity_name',
                    'user.truename' => 'name',
                    'activity.img' => 'img',
                    'activity.address' => 'address',
                    'from_unixtime(detail.begin_time, "%Y-%m-%d %H:%i:%S")' => 'begin_time',
                    'from_unixtime(detail.end_time, "%Y-%m-%d %H:%i:%S")' => 'end_time',
                    'detail.content' => 'content',
                    'detail.linkman' => 'linkman',
                    'activity.phone' => 'phone',
                    'detail.annotation' => 'annotation',
                    'activity.status' => 'status',
                    'type.id' => 'type_id'
                ])
                ->find();
            $data['img'] = joint_domain_pre($data['img']);
            $activityType = Db::table('activity_type')->select();
            return $this->fetch('', [
                'title' => '活动列表 -> 编辑',
                'data' => $data,
                'activity_type' => $activityType
            ]);
        }
        if($this->request->isPost()) {
            $params = [
                'activity_id' => input('id', null)
            ];
            $list = [
                'phone' => input('phone', ''),
                'img' => empty(input('img', '')) ? '' : remove_domain_pre(input('img')),
                'address' => input('address', ''),
                'status' => input('status/d'),
                'activity_type_id' => input('activity_type/d', 1)
            ];
            $public = [
                'title' => input('title', ''),
                'linkman' => input('linkman', ''),
                'begin_time' => empty(input('begin_time', null)) ? time() : strtotime(input('begin_time')),
                'end_time' => empty(input('end_time', null)) ? time() : strtotime(input('end_time'))
            ];
            $detail = [
                'content' => input('content', ''),
                'annotation' => input('annotation', '')
            ];
            Db::startTrans();
            try {
                Db::table('activity')->where('id', $params['activity_id'])->update(array_merge($public, $list));
                Db::table('activity_detail')->where('activity_id', $params['activity_id'])->update(array_merge($public, $detail));
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $this->error('活动编辑失败，请重试', '');
                Db::rollback();
            }
            $this->success('编辑成功', gen_url('index'));
        }
    }
    public function del()
    {
        $params = [
            'activity_id' => input('id', null)
        ];
        Db::startTrans();
        try {
            Db::table('activity')->where('id', $params['activity_id'])->delete();
            Db::table('activity')->where('activity_id', $params['activity_id'])->delete();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $this->error('活动删除失败', '');
            Db::rollback();
        }
        $this->success('删除成功', '');
    }
    
    public function audit()
    {
        $params = [
            'activity_name' => input('name', '')
        ];
        $db = Db::table('activity')
            ->alias('activity')
            ->join(['activity_user' => 'activity_user'], 'activity_user.activity_id = activity.id', 'left')
            ->join(['activity_type' => 'type'], 'type.id = activity.activity_type_id', 'left')
            ->join(['user' => 'user'], 'user.id = area_user_id', 'left')
            ->group('activity.id')
            ->field([
                'activity.id' => 'activity_id',
                'activity.title' => 'activity_name',
                'type.name' => 'type_name',
                'user.truename' => 'truename',
                'count(activity_user.id)' => 'activity_sum'
            ])
            ->order('activity.create_time desc');
        if(!empty($params['activity_name'])) {
            $db->where('activity.title', 'like', '%'.$params['activity_name'].'%');
        }
        $this->assign([
            'title' => '活动列表'
        ]);
        return $this->viewPaginated($db);
    }

    public function audit_list()
    {
        $params = [
            'activity_id' => input('id', ''),
            'user_name' => input('name', ''),
            'is_check' => input('is_check/d', 'all')
        ];
        $db = Db::table('activity_user')
            ->alias('activity_user')
            ->join(['user' => 'user'], 'user.id = activity_user.user_id')
            ->where('activity_user.activity_id', $params['activity_id'])
            ->field([
                'user.id' => 'id',
                'user.truename' => 'name',
                'user.level_id' => 'level',
                'activity_user.is_check' => 'is_check', 
                'activity_user.create_time' => 'create_time',
                'activity_user.audit_time' => 'audit_time'
            ])
            ->order([
                'activity_user.is_check' => 'desc',
                'activity_user.create_time' => 'desc'
            ]);
        if(($params['is_check'] === 0) || ($params['is_check'] === 1) || ($params['is_check'] === -1)) {
            $db->where('activity_user.is_check', $params['is_check']);
        }
        if(!empty($params['user_name'])) {
            $db->where('user.truename', 'like', '%'.$params['user_name'].'%');
        }
        $area_user = Db::table('activity')
            ->alias('activity')
            ->join(['user' => 'user'], 'user.id = activity.area_user_id')
            ->join(['activity_type' => 'type'], 'type.id = activity.activity_type_id')
            ->where('activity.id', $params['activity_id'])
            ->field([
                'activity.title' => 'title',
                'user.truename' => 'truename',
                'type.name' => 'type_name'
            ])
            ->find();
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
            'title' => '活动列表 -> 审核列表',
            'area_user' => $area_user,
            'select' => $select
        ]);
        return $this->viewPaginated($db);
    }

    public function audit_detail()
    {
        $params = [
            'user_id' => input('user_id', null),
            'activity_id' => input('activity_id', null)
        ];
        $data = Db::table('activity_user')
            ->alias('activity_user')
            ->join(['user' => 'user'], 'user.id = activity_user.user_id')
            ->where('activity_user.activity_id', $params['activity_id'])
            ->where('user.id', $params['user_id'])
            ->field([
                'user.truename' => 'name',
                'user.sex' => 'sex',
                'user.phone' => 'phone',
                'user.level_id' => 'level',
                'user.area_code' => 'code',
                'activity_user.is_check' => 'is_check',
                'activity_user.error_msg' => 'error_msg',
                'activity_user.audit_time' => 'audit_time'
            ])
            ->find();
        return $this->view->fetch('', [
            'data' => $data
        ]);
    }
    public function audit_edit()
    {
        $params = [
            'user_id' => input('user_id', null),
            'activity_id' => input('activity_id', null)
        ];
        if($this->request->isGet()){
            $data = Db::table('activity_user')
                ->alias('activity_user')
                ->join(['user' => 'user'], 'user.id = activity_user.user_id')
                ->where('activity_user.activity_id', $params['activity_id'])
                ->where('user.id', $params['user_id'])
                ->field([
                    'user.id' => 'id',
                    'activity_user.activity_id' => 'activity_id',
                    'activity_user.is_check' => 'is_check',
                    'activity_user.error_msg' => 'error_msg',
                    'user.truename' => 'name',
                    'user.sex' => 'sex',
                    'user.phone' => 'phone',
                    'user.level_id' => 'level',
                    'user.area_code' => 'code',
                ])
                ->find();
            return $this->view->fetch('', [
                'data' => $data
            ]);
        }
        if($this->request->isPost())
        {
            $list = [
                'is_check' => input('is_check/d', 0),
                'error_msg' => input('error_msg', ''),
                'audit_time' => date('Y-m-d h:i:s', time())
            ];
            $isUpdate = Db::table('activity_user')
                ->where('user_id', $params['user_id'])
                ->where('activity_id', $params['activity_id'])
                ->update($list);
            if($isUpdate) {
                $this->success('审核成功', '');
            }
            $this->error('审核失败，请重试', '');
        }
    }
}
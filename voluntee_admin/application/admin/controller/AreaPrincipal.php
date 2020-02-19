<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think\Db;

class AreaPrincipal extends BasicAdmin2
{

    private $agent_id;
    public function _initialize($value='')
    {
        $user = session('user');
        $this->agent_id = $user['agent_id'];
    }
    
    public function index(){
        return $this->view->fetch('', [
            'list' => [],
            'title' => '区域负责人管理'
        ]);
        // return $this->viewPaginated($db);
    }
    
    public function add()
    {
        if($this->request->isGet()) {
            $params = [
                'search' => input('search', ''),
                'action' => input('action', null)
            ];
            $db = Db::table('user')
                ->where('is_check', 1)
                ->field([
                    'id',
                    'truename',
                    'phone',
                    'sex',
                ]);
            switch ($params['action']) {
                case null:
                    return $this->view->fetch('add', ['list' => $db->select()]);
                    break;
                case 'get_yigong':
                    $data = $db->where('truename|phone', 'like', '%'.$params['search'].'%')->order(['id' => 'desc'])->select();
                    return $this->view->fetch('item', ['list' => $data]);
                    break;
                case 'next':
                    $province = Db::table('wy_area')->where('tid', 0)->field(['id', 'code', 'name'])->select();
                    return $this->view->fetch('add_next', ['id' => input('id/d'), 'province' => $province]);
                    break;
                // case 'addUser':
                //     $area = [
                //         'area_id' => input('area_id/a', []),
                //         'area_code' => input('area_code/a', [])
                //     ];
                //     return $this->view->fetch('add_user', ['id' => input('id/d'), 'area' => $area]);
                //     break;
                default:
                    return $this->view->fetch('add', ['list' => $db->select()]);
                    break;
            }
        }
        if($this->request->isPost() && (input('action') === 'addUser')) {
            $area = [
                'area_id' => input('area_id/a', []),
                'area_code' => input('area_code/a', []),
                'area_name' => input('area_name/a', [])
            ];
            return $this->view->fetch('add_user', ['id' => input('id/d'), 'area' => $area]);
        }
        if($this->request->isPost()) {
            $params = [
                'id' => input('id/d', null),
                'user_name' => input('username', ''),
                'password' => input('password', ''),
                'repeat_password' => input('repeat_password', ''),
                'area_name' => input('area_name/a', []),
                'area_id' => input('area_id/a', []),
                'area_code' => input('area_code/a', [])
            ];
            $isHas = Db::table('system_user')->where('username', $params['user_name'])->count();
            if($params['password'] !== $params['repeat_password']) {
                $this->error('两次密码不一致，请检查');
            }
            if($isHas >= 1) {
                $this->error('该账户已在使用，请另起账户');
            }
            $user = Db::table('user')->where('id', $params['id'])->field(['id', 'phone', 'openid'])->find();
            $list = [
                'agent_id' => 1, 
                'username' => $params['user_name'],
                'password' => md5($params['password']),
                'phone' => $user['phone'],
                'openid' => $user['openid'],
                'create_at' => date('Y-m-d h:i:s', time()),
            ];
            $user_area = [];
            foreach($params['area_id'] as $key => $vo) {
                $temp = [
                    'user_id' => $user['id'],
                    'wy_area_id' => $vo,
                    'wy_area_name' => $params['area_name'][$key],
                    'wa_area_code' => $params['area_code'][$key]
                ];
                array_push($user_area, $temp);
            }
            Db::startTrans();
            try {
                $systemUserId = Db::table('system_user')->insertGetId($list);
                Db::table('user')->where('id', $user['id'])->update(['system_user_id' => $systemUserId]);
                Db::table('user_area')->insertAll($user_area);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $this->error('负责人创建失败，请重试');
                Db::rollback();
            }
            $this->success('创建成功', '');
        }
    }

    public function get_area($id = 0)
    {
        $params = [
            'id' => input('id')
        ];
        $data = Db::table('wy_area')
            ->where('tid', $params['id'])
            ->field(['id', 'code', 'name'])
            ->order(['id' => 'asc'])
            ->select();
        return $data;
    }

}
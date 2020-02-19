<?php

namespace app\admin\controller;

use controller\BasicAdmin2;
use think;
use library;
use Endroid\QrCode\QrCode;
use think\Db;
use think\Session;


class User extends BasicAdmin2
{
    public function index()
    {
        $db = Db::table('system_user')
            ->alias('user')
            ->where('agent_id',Session::get('agent_id'))
            ->field([
                'user.id',
                'user.username',
                'user.phone',
                'user.mail',
                'user.login_num',
                'user.login_at',
                'user.openid',
                'user.status',
                'user.authorize'
            ]);

        $username = trim(input('username', ''));
        if ($username !== '') {
            $db->where('username', 'LIKE', '%' . $username . '%');
        }
        $phone = trim(input('phone', ''));
        if ($phone !== '') {
            $db->where('phone', $phone);
        }

        $paginator = (new library\DbPaginator($db))->each(function ($item) {
            $item['has_openid'] = $item['openid'] ? true : false;
            unset($item['openid']);
            return $item;
        });
        return $this->viewPaginated($paginator);
    }


    public function add()
    {
        if ($this->request->isGet()) {
            return $this->view->fetch('', ['authorizes' =>$this->getAuthorizeArray()]);
        }

        if ($this->request->isPost()) {
            $this->validateUsername(input('username'));
            $this->validatePhone(input('phone'));
            $this->validatePassword(input('password'), input('repassword'));

            $data = [
                'username' => input('username'),
                'phone' => input('phone'),
                'password' => md5(input('password')),
                'authorize' => implode(',', input('authorize/a', [])),
                'agent_id' => Session::get('agent_id'),
                'mail' => input('mail', null),
                'desc' => input('desc', null),
            ];
            $id = Db::table('system_user')->insertGetId($data);
            $this->success('操作成功', '', ['id' => $id]);
        }
    }


    public function qrcode()
    {
        $data = Db::table('system_user')
            ->where('id', input('id'))
            ->field(['id', 'username'])
            ->find();
        $qrcode_bin = (new QrCode())
            ->setText(self::genBindUserUrl($data['id']))
            ->setSize(220)
            ->get('jpg');
        $img_src = implode(',', ['data:image/jpeg;base64', base64_encode($qrcode_bin)]);

        return $this->view->fetch('', ['img_src' => $img_src, 'name' => $data['username']]);
    }


    public function edit()
    {
        if ($this->request->isGet()) {
            $data = Db::table('system_user')
                ->where('id', input('id'))
                ->find();
            $data['authorize'] = explode(',', $data['authorize']);

            return $this->view->fetch('', [
                'data' => $data,
                'authorizes' =>$this->getAuthorizeArray()
            ]);
        }

        if ($this->request->isPost()) {
            $system_user = Db::table('system_user')
                ->where('id', input('id'))
                ->find();
            if (input('username') !== $system_user['username']) {
                $this->validateUsername(input('username'));
            }

            if (input('phone') !== $system_user['phone']) {
                $this->validatePhone(input('phone'));
            }
            if($system_user['authorize'] == config('auth.root_auth_id')){
                $authorize = config('auth.root_auth_id');
            }else{
                $authorize = implode(',', input('authorize/a', []));
            }
           
            $data = [
                'phone' => input('phone'),
                'authorize' => $authorize,
                'agent_id' => Session::get('agent_id'),
                'mail' => input('mail', null),
                'desc' => input('desc', null),
            ];
            Db::table('system_user')->where('id', input('id'))->update($data);
            return $this->resultMessage(true);
        }
    }


    public function auth()
    {
        if ($this->request->isGet()) {
            $data = Db::table('system_user')
                ->where('id', input('id'))
                ->find();
            $data['authorize'] = explode(',', $data['authorize']);

            return $this->view->fetch('', [
                'data' => $data,
                'authorizes' => $this->getAuthorizeArray()
            ]);
        }

        if ($this->request->isPost()) {
            Db::table('system_user')
                ->where('id', input('id'))
                ->where('agent_id', Session::get('agent_id'))
                ->update(['authorize' => implode(',', input('authorize/a', []))]);
            return $this->resultMessage(true);
        }
    }


    public function password()
    {
        if ($this->request->isGet()) {
            $data = Db::table('system_user')
                ->where('id', input('id'))
                ->find();
            return $this->view->fetch('', ['data' => $data]);
        }

        if ($this->request->isPost()) {
            $this->validatePassword(input('password'), input('repassword'));

            Db::table('system_user')
                ->where('id', input('id'))
                ->where('agent_id', Session::get('agent_id'))
                ->update(['password' => md5(input('password'))]);
            return $this->resultMessage(true);
        }
    }


    public function batchDel()
    {
        $id_array = explode(',', input('id'));
        foreach ($id_array as $id) {
            $this->validateUserId((int)$id);
        }
        Db::table('system_user')
            ->whereIn('id', $id_array)
            ->where('agent_id', Session::get('agent_id'))
            ->delete();
        return $this->resultMessage(true);
    }
    
    
    public function del()
    {
        $this->validateUserId((int)input('id'));

        Db::table('system_user')
            ->where('id', input('id'))
            ->where('agent_id', Session::get('agent_id'))
            ->delete();
        $this->resultMessage(true);
    }


    public function status()
    {
        $this->validateUserId((int)input('id'));

        Db::table('system_user')
            ->where('id', input('id'))
            ->where('agent_id', Session::get('agent_id'))
            ->update(['status' => input('value')]);
        $this->resultMessage(true);
    }


    private function validatePassword($password, $repassword)
    {
        if ($password !== $repassword) {
            $this->error('两次输入的密码不一致');
        }
    }


    private function validateUserId($id)
    {
        $super_user_id = 10000;
        $current_user_id = (int)Session::get('user.id');

        if ($id === $super_user_id) {
            $this->error('禁止操作系统超级用户');
        }
        if ($id === $current_user_id) {
            $this->error('禁止操作当前用户');
        }
    }


    private function validateUsername($name)
    {
        $count = Db::table('system_user')
            ->where('username', $name)
            ->count();
        if ($count > 0) {
            $this->error("该用户名称已存在");
        }
    }


    private function validatePhone($phone)
    {
        $count = Db::table('system_user')
            ->where('phone', $phone)
            ->count();
        if ($count > 0) {
            $this->error("该手机号已存在");
        }
    }


    private function getAuthorizeArray()
    {
        return Db::table('system_auth')
            ->where('status', 1)
            ->where('agent_id',Session::get('agent_id'))
            ->field(['id', 'title'])
            ->select();
    }


    private static function genBindUserUrl($id)
    {
        $wx_domain = think\Config::get('host.weixin_domain');

        $key = think\Config::get('aes_key');
        $encrypted_id = (new library\Aes($key))->encrypt($id);

        $url = $wx_domain . 'bindManage/' . base64_encode($encrypted_id);

        return $url;
    }
}

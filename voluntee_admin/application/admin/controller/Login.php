<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use library;
use service\LogService;
use service\NodeService;
use think\Db;
use think\captcha\Captcha;

/**
 * 系统登录控制器
 * class Login
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/10 13:59
 */
class Login extends BasicAdmin
{

    /**
     * 默认检查用户登录状态
     * @var bool
     */
    public $checkLogin = false;

    /**
     * 默认检查节点访问权限
     * @var bool
     */
    public $checkAuth = false;

    /**
     * 控制器基础方法
     */
    public function _initialize()
    {
        if (session('user') && $this->request->action() !== 'out') {
            $this->redirect('@admin');
        }
    }

    /**
     * 用户登录
     * @return string
     */
    public function index()
    {
        if ($this->request->isGet()) {
            return $this->fetch('', ['title' => '用户登录']);
        }

        if ($this->request->isPost()) {
            // 输入数据效验
            $username = $this->request->post('username', '', 'trim');
            $password = $this->request->post('password', '', 'trim');
            $captcha = $this->request->post('vcode', '', 'trim');
            strlen($username) < 4 && $this->error('登录账号长度不能少于4位有效字符!');
            strlen($password) < 4 && $this->error('登录密码长度不能少于4位有效字符!');

            $login_captcha = config('login_captcha');
            // 检查验证码
            if($login_captcha && !captcha_check($captcha)){
                $this->error('验证码错误');
            };

            // 用户信息验证
            $user = Db::name('SystemUser')
                ->where('username|phone', $username)
                ->where("is_deleted", 0)
                ->where('agent_id','<>',0)
                ->find();
            empty($user) && $this->error('登录账号不存在，请重新输入!');
            ($user['password'] !== md5($password)) && $this->error('登录密码与账号不匹配，请重新输入!');
            empty($user['status']) && $this->error('账号已经被禁用，请联系管理!');

            // 更新登录信息
            $data = ['login_at' => Db::raw('now()'), 'login_num' => Db::raw('login_num+1')];
            Db::name('SystemUser')->where('id', $user['id'])->update($data);
            // library\Login::setExpire(1 * 60 * 60);
            session('user', $user);
            session('agent_id', $user['agent_id']);
            !empty($user['authorize']) && NodeService::applyAuthNode();
            LogService::write('系统管理', '用户登录系统成功');
            $this->success('登录成功，正在进入系统...', '@admin');
        }
    }

    public function verify()
    {
        $captcha = new Captcha();
        $captcha->fontSize = 30;
        $captcha->length   = 4;
        $captcha->useNoise = false;
        $captcha->useCurve = false;
        return $captcha->entry();  
    }


    /**
     * 退出登录
     */
    public function out()
    {
        session('user', null);
        session('agent_id', null);
        session_destroy();
        LogService::write('系统管理', '用户退出系统成功');
        $this->success('退出登录成功！', '@admin/login');
    }

    public function test(){
        return $this->fetch();
    }
}

<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use service\NodeService;
use service\ToolsService;
use think\Db;
use think\Session;
use think\View;

/**
 * 后台入口
 * Class Index
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 10:41
 */
class Index extends BasicAdmin
{

    /**
     * 后台框架布局
     * @return View
     */
    public function index()
    {
        /*NodeService::applyAuthNode();
        $list = Db::name('SystemMenu')->where('status', '1')->order('sort asc,id asc')->select();
        $menus = $this->_filterMenu(ToolsService::arr2tree($list));
        return view('', ['title' => '系统管理', 'menus' => $menus]);*/
    
        NodeService::applyAuthNode();
        $list = (array)Db::name('SystemMenu')->where(['status' => '1', 'type'=>1])->order('sort asc,id asc')->select();
        
        $menus = $this->buildMenuData(ToolsService::arr2tree($list), NodeService::get(), !!session('user'));
       
        if (empty($menus) && !session('user.id')) {
            $this->redirect('@admin/login');
        }
        return $this->fetch('', ['title' => '系统管理', 'menus' => $menus]);
    }
    
    /**
     * 后台主菜单权限过滤
     * @param array $menus 当前菜单列表
     * @param array $nodes 系统权限节点数据
     * @param bool $isLogin 是否已经登录
     * @return array
     */
    private function buildMenuData($menus, $nodes, $isLogin)
    {
        foreach ($menus as $key => &$menu) {
            !empty($menu['sub']) && $menu['sub'] = $this->buildMenuData($menu['sub'], $nodes, $isLogin);
            if (!empty($menu['sub'])) {
                $menu['url'] = '#';
            } elseif (preg_match('/^https?\:/i', $menu['url'])) {
                continue;
            } elseif ($menu['url'] !== '#') {
                $node = join('/', array_slice(explode('/', preg_replace('/[\W]/', '/', $menu['url'])), 0, 3));
                $menu['url'] = url($menu['url']) . (empty($menu['params']) ? '' : "?{$menu['params']}");
                if (isset($nodes[$node]) && $nodes[$node]['is_login'] && empty($isLogin)) {
                    unset($menus[$key]);
                } elseif (isset($nodes[$node]) && $nodes[$node]['is_auth'] && $isLogin && !auth($node)) {
                    unset($menus[$key]);
                }
            } else {
                unset($menus[$key]);
            }
        }
        return $menus;
    }

    /**
     * 后台主菜单权限过滤
     * @param array $menus
     * @return array
     */
   /* private function _filterMenu($menus)
    {
        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = $this->_filterMenu($menu['sub']);
            }
            if (!empty($menu['sub'])) {
                $menu['url'] = '#';
            } elseif (stripos($menu['url'], 'http') === 0) {
                continue;
            } elseif ($menu['url'] !== '#' && auth(join('/', array_slice(explode('/', $menu['url']), 0, 3)))) {
                $menu['url'] = url($menu['url']);
            } else {
                unset($menus[$key]);
            }
        }
        return $menus;
    }*/

    /**
     * 修改密码
     */
    public function pass()
    {
        if ($this->request->isGet()) {
            $data = Db::table('system_user')
                ->where('id', Session::get('user.id'))
                ->find();
            return $this->view->fetch('', ['data' => $data]);
        }

        if ($this->request->isPost()) {
            $this->validateCurrentPassword(input('current_password'));
            $this->validatePassword(input('password'), input('repassword'));

            Db::table('system_user')
                ->where('id', Session::get('user.id'))
                ->update(['password' => md5(input('password'))]);
            $this->success('操作成功', '');
        }
    }

    /**
     * 修改资料
     */
    public function info()
    {
        if ($this->request->isGet()) {
            $data = Db::table('system_user')
                ->where('id', Session::get('user.id'))
                ->find();

            return $this->view->fetch('', ['data' => $data,]);
        }

        if ($this->request->isPost()) {
            $current_phone = Db::table('system_user')
                ->where('id', Session::get('user.id'))
                ->value('phone');
            if (input('phone') !== $current_phone) {
                $this->validatePhone(input('phone'));
            }

            $data = [
                'phone' => input('phone'),
                'mail' => input('mail', null),
                'desc' => input('desc', null),
            ];
            Db::table('system_user')->where('id', Session::get('user.id'))->update($data);
            $this->success('操作成功', '');
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


    private function validateCurrentPassword($password)
    {
        $current_password_md5 = Db::table('system_user')
            ->where('id', Session::get('user.id'))
            ->value('password');
        if (md5($password) !== $current_password_md5) {
            $this->error('旧的密码错误');
        }
    }


    private function validatePassword($password, $repassword)
    {
        if ($password !== $repassword) {
            $this->error('两次输入的密码不一致');
        }
    }
}

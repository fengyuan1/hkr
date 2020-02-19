<?php

namespace hook;

use library;
use think\Config;
use think\exception\HttpResponseException;
use think\Request;
use think\View;
use service\NodeService;
use think\Db;

/**
 * 访问权限管理
 * Class AccessAuth
 * @package hook
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/05/12 11:59
 */
class AccessAuth
{

    /**
     * 当前请求对象
     * @var Request
     */
    protected $request;
    
    /**
     * 行为入口
     * @param $params
     */
    public function run(&$params)
    {
        $this->request = Request::instance();
        list($module, $controller, $action) = [
            $this->request->module(),
            $this->request->controller(),
            $this->request->action()
        ];
    
        $access = $this->buildAuth($node = NodeService::parseNodeStr("{$module}/{$controller}/{$action}"));
        
        // 登录状态检查
        if (!empty($access['is_login']) && !session('user')) {
            $msg = ['code' => 0, 'msg' => '抱歉，您还没有登录获取访问权限！', 'url' => url('@admin/login')];
            throw new HttpResponseException($this->request->isAjax() ? json($msg) : redirect($msg['url']));
        }
        // 访问权限检查
        if (!empty($access['is_auth']) && !auth($node)) {
            throw new HttpResponseException(json(['code' => 0, 'msg' => '抱歉，您没有访问该模块的权限！']));
        }
        
        /*$vars = get_class_vars(config('app_namespace') . "\\{$module}\\controller\\{$controller}");

        // 用户登录状态检查
        if ((!empty($vars['checkAuth']) || !empty($vars['checkLogin'])) && !session('user')) {
            if ($this->request->isAjax()) {
                $result = [
                    'code' => 0,
                    'msg' => '请先登录获取访问权限',
                    'data' => '',
                    'url' => url('@admin/login'),
                    'wait' => 3
                ];
                throw new HttpResponseException(json($result));
            }
            throw new HttpResponseException(redirect('@admin/login'));
        }*/

        // 登录超时检查
        // if (!empty($vars['checkLogin']) && !library\Login::isValid()) {
        //     if ($this->request->isAjax()) {
        //         $result = [
        //             'code' => 0,
        //             'msg' => '登录超时，请重新登录',
        //             'data' => '',
        //             'url' => url('@admin/login'),
        //             'wait' => 3
        //         ];
        //         throw new HttpResponseException(json($result));
        //     }
        //     throw new HttpResponseException(redirect('@admin/login'));
        // }

        // 访问权限节点检查
       /* if (!empty($vars['checkLogin']) && !auth("{$module}/{$controller}/{$action}")) {
            $result = [
                'code' => 0,
                'msg' => '您没有访问该模块的权限',
                'data' => '',
                'url' => '',
                'wait' => 3
            ];
            throw new HttpResponseException(json($result));
        }*/

        // 权限正常, 默认赋值
        $view = View::instance(Config::get('template'), Config::get('view_replace_str'));
        $view->assign('classuri', NodeService::parseNodeStr("{$module}/{$controller}"));
    }
    
    /**
     * 根据节点获取对应权限配置
     * @param string $node 权限节点
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function buildAuth($node)
    {
        $info = Db::name('SystemNode')->cache(true, 30)->where(['node' => $node])->find();
        return [
            'is_menu'  => intval(!empty($info['is_menu'])),
            'is_auth'  => intval(!empty($info['is_auth'])),
            'is_login' => empty($info['is_auth']) ? intval(!empty($info['is_login'])) : 1,
        ];
    }
}
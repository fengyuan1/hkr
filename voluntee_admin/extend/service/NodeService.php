<?php



namespace service;

use think\Db;

/**
 * 系统权限节点读取器
 * Class NodeService
 * @package extend
 * 
 * @date 2017/05/08 11:28
 */
class NodeService
{

    /**
     * 应用用户权限节点
     * @return bool
     */
    public static function applyAuthNode()
    {
        cache('need_access_node', null);
        if (($userid = session('user.id'))) {
            session('user', Db::name('SystemUser')->where('id', $userid)->find());
        }
        if (($authorize = session('user.authorize'))) {
            $where = ['id' => ['in', explode(',', $authorize)], 'status' => '1'];
            $authorizeids = Db::name('SystemAuth')->where($where)->column('id');
            if (empty($authorizeids)) {
                return session('user.nodes', []);
            }
            $nodes = Db::name('SystemAuthNode')->where('auth', 'in', $authorizeids)->column('node');
            return session('user.nodes', $nodes);
        }
        return false;
    }

    /**
     * 获取授权节点
     * @return array
     */
    public static function getAuthNode()
    {
        $nodes = cache('need_access_node');
        if (empty($nodes)) {
            $nodes = Db::name('SystemNode')->where('is_auth', '1')->column('node');
            cache('need_access_node', $nodes);
        }
        return $nodes;
    }

    /**
     * 检查用户节点权限
     * @param string $node 节点
     * @return bool
     */
    public static function checkAuthNode($node)
    {
        list($module, $controller, $action) = explode('/', str_replace(['?', '=', '&'], '/', $node . '///'));
        $auth_node = trim("{$module}/{$controller}/{$action}", '/');
        if (session('user.id') === config('auth.root_user_id') || stripos($node, 'admin/index') === 0) {
            return true;
        }
        if (!in_array($auth_node, self::getAuthNode())) {
            return true;
        }
        return in_array($auth_node, (array)session('user.nodes'));
    }

    /**
     * 获取系统代码节点
     * @return array
     */
    /*public static function get()
    {
        $alias = [];
        foreach (Db::name('SystemNode')->select() as $vo) {
            $alias["{$vo['node']}"] = $vo;
        }
        $nodes = [];
        $ignore = [
            'index',
            'wechat/api', 'wechat/notify', 'wechat/review',
            'admin/plugs', 'admin/login', 'admin/index',
        ];
        foreach (self::getNodeTree(APP_PATH) as $thr) {
            foreach ($ignore as $str) {
                if (stripos($thr, $str) === 0) {
                    continue 2;
                }
            }
            $tmp = explode('/', $thr);
            $one = $tmp[0];
            $two = "{$tmp[0]}/{$tmp[1]}";
            $nodes[$one] = array_merge(isset($alias[$one]) ? $alias[$one] : ['node' => $one, 'title' => '', 'is_menu' => 0, 'is_auth' => 0], ['pnode' => '']);
            $nodes[$two] = array_merge(isset($alias[$two]) ? $alias[$two] : ['node' => $two, 'title' => '', 'is_menu' => 0, 'is_auth' => 0], ['pnode' => $one]);
            $nodes[$thr] = array_merge(isset($alias[$thr]) ? $alias[$thr] : ['node' => $thr, 'title' => '', 'is_menu' => 0, 'is_auth' => 0], ['pnode' => $two]);
        }
        return $nodes;
    }*/
    
    /**
     * 获取系统代码节点
     * @param array $nodes
     * @return array
     */
    public static function get($nodes = [])
    {
        $alias = Db::name('SystemNode')->column('node,is_menu,is_auth,is_login,title');
        $ignore = [
            'index',
            'common',
            'wechat/review',
            'admin/plugs',
            'admin/login',
            'admin/index',
            'super/login',
            'super/index',
        ];
        foreach (self::getNodeTree(APP_PATH) as $thr) {
            foreach ($ignore as $str) {
                if (stripos($thr, $str) === 0) {
                    continue 2;
                }
            }
            $tmp = explode('/', $thr);
            list($one, $two) = ["{$tmp[0]}", "{$tmp[0]}/{$tmp[1]}"];
            $nodes[$one] = array_merge(isset($alias[$one]) ? $alias[$one] : ['node' => $one, 'title' => '', 'is_menu' => 0, 'is_auth' => 0, 'is_login' => 0], ['pnode' => '']);
            $nodes[$two] = array_merge(isset($alias[$two]) ? $alias[$two] : ['node' => $two, 'title' => '', 'is_menu' => 0, 'is_auth' => 0, 'is_login' => 0], ['pnode' => $one]);
            $nodes[$thr] = array_merge(isset($alias[$thr]) ? $alias[$thr] : ['node' => $thr, 'title' => '', 'is_menu' => 0, 'is_auth' => 0, 'is_login' => 0], ['pnode' => $two]);
        }
        foreach ($nodes as &$node) {
            list($node['is_auth'], $node['is_menu'], $node['is_login']) = [intval($node['is_auth']), intval($node['is_menu']), empty($node['is_auth']) ? intval($node['is_login']) : 1];
        }
        return $nodes;
    }

    /**
     * 获取节点列表
     * @param string $path 路径
     * @param array $nodes 额外数据
     * @return array
     */
    /*public static function getNodeTree($path, $nodes = [])
    {
        foreach (self::_getFilePaths($path) as $vo) {
            if (!preg_match('|/(\w+)/controller/(\w+)|', str_replace(DS, '/', $vo), $matches) || count($matches) !== 3) {
                continue;
            }
            $className = config('app_namespace') . str_replace('/', '\\', $matches[0]);
            if (!class_exists($className)) {
                continue;
            }
            foreach (get_class_methods($className) as $actionName) {
                if ($actionName[0] !== '_') {
                    $nodes[] = "{$matches[1]}/{$matches[2]}/{$actionName}";
                }
            }
        }
        return $nodes;
    }*/
    
    /**
     * 获取节点列表
     * @param string $dirPath 路径
     * @param array $nodes 额外数据
     * @return array
     */
    public static function getNodeTree($dirPath, $nodes = [])
    {
        foreach (self::_getFilePaths($dirPath) as $filename) {
            $matches = [];
            if (!preg_match('|/(\w+)/controller/(\w+)|', str_replace(DIRECTORY_SEPARATOR, '/', $filename), $matches) || count($matches) !== 3) {
                continue;
            }
            $className = config('app_namespace') . str_replace('/', '\\', $matches[0]);
            if (!class_exists($className)) continue;
            foreach (get_class_methods($className) as $funcName) {
                if (strpos($funcName, '_') !== 0 && $funcName !== '_initialize') {
                    $nodes[] = self::parseNodeStr("{$matches[1]}/{$matches[2]}") . '/' . strtolower($funcName);
                }
            }
        }
        return $nodes;
    }
    
    /**
     * 驼峰转下划线规则
     * @param string $node
     * @return string
     */
    public static function parseNodeStr($node)
    {
        $tmp = [];
        foreach (explode('/', $node) as $name) {
            $tmp[] = strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
        return trim(join('/', $tmp), '/');
    }

    /**
     * 获取所有PHP文件
     * @param string $path 目录
     * @param array $data 额外数据
     * @param string $ext 文件后缀
     * @return array
     */
    private static function _getFilePaths($path, $data = [], $ext = 'php')
    {
        foreach (scandir($path) as $dir) {
            if ($dir[0] === '.') {
                continue;
            }
            if (($tmp = realpath($path . DS . $dir)) && (is_dir($tmp) || pathinfo($tmp, PATHINFO_EXTENSION) === $ext)) {
                is_dir($tmp) ? $data = array_merge($data, self::_getFilePaths($tmp)) : $data[] = $tmp;
            }
        }
        return $data;
    }

}

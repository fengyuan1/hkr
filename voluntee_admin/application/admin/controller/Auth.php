<?php



namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use service\NodeService;
use service\ToolsService;
use think\Db;
use think\Session;

/**
 * 系统权限管理控制器
 * Class Auth
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 18:13
 */
class Auth extends BasicAdmin
{

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'SystemAuth';
    
    
    public function _initialize()
    {
        parent::_initialize();
        $this->db = Db::name($this->table)->where('agent_id',Session::get('agent_id'));
    }
    
    /**
     * 权限列表
     */
    public function index()
    {
        $this->title = '系统权限管理';
        return parent::_list($this->db);
    }

    /**
     * 权限授权
     * @return string
     */
    public function apply()
    {
        $auth_id = $this->request->get('id', '0');
        $method = '_apply_' . strtolower($this->request->get('action', '0'));
        if (method_exists($this, $method)) {
            return $this->$method($auth_id);
        }
        $this->assign('title', '节点授权');
        return $this->_form($this->table, 'apply');
    }

    /**
     * 读取授权节点
     * @param $auth_id
     */
    protected function _apply_getnode($auth_id)
    {
        $nodes = NodeService::get();
        $checked = Db::name('SystemAuthNode')->where('auth', $auth_id)->column('node');
        
        foreach ($nodes as $key => &$node) {
            $node['checked'] = in_array($node['node'], $checked);
            if (empty($node['is_auth']) && substr_count($node['node'], '/') > 1) {
                unset($nodes[$key]);
            }
        }
        
        $allnode = $this->_apply_filter(ToolsService::arr2tree($nodes, 'node', 'pnode', '_sub_'));
       
        $this->success('获取节点成功!', '', $allnode);
    }

    /**
     * 保存授权节点
     * @param $auth_id
     */
    protected function _apply_save($auth_id)
    {
        $data = [];
        $post = $this->request->post();
        foreach (isset($post['nodes']) ? $post['nodes'] : [] as $node) {
            $data[] = ['auth' => $auth_id, 'node' => $node];
        }
        Db::name('SystemAuthNode')->where('auth', $auth_id)->delete();
        Db::name('SystemAuthNode')->insertAll($data);
        $this->success('节点授权更新成功!', '');
    }

    /**
     * 节点数据拼装
     * @param array $nodes
     * @param int $level
     * @return array
     */
    protected function _apply_filter($nodes, $level = 1)
    {
        foreach ($nodes as $key => &$node) {
            if($node['node'] == 'super'){
                unset($node);
            }
            if (!empty($node['_sub_']) && is_array($node['_sub_'])) {
                $node['_sub_'] = $this->_apply_filter($node['_sub_'], $level + 1);
            } elseif ($level < 3) {
                unset($nodes[$key]);
            }
        }
        return $nodes;
    }

    /**
     * 权限添加
     */
    public function add()
    {
        return $this->_form($this->table, 'form');
    }

    /**
     * 权限编辑
     */
    public function edit()
    {
        return $this->_form($this->table, 'form');
    }
    
    protected function _form_filter(&$vo){
        $vo['agent_id'] = Session::get('agent_id');
    }

    /**
     * 权限禁用
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("权限禁用成功!", '');
        }
        $this->error("权限禁用失败, 请稍候再试!");
    }

    /**
     * 权限恢复
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("权限启用成功!", '');
        }
        $this->error("权限启用失败, 请稍候再试!");
    }

    /**
     * 权限删除
     */
    public function del()
    {
        if (DataService::update($this->table)) {
            $id = $this->request->post('id');
            Db::name('SystemAuthNode')->where('auth', $id)->delete();
            $this->success("权限删除成功!", '');
        }
        $this->error("权限删除失败, 请稍候再试!");
    }

}

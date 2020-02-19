<?php



namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use service\NodeService;
use service\ToolsService;
use think\Db;

/**
 * 系统功能节点管理
 * Class Node
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 18:13
 */
class Node extends BasicAdmin
{

    /**
     * 指定当前默认模型
     * @var string
     */
    public $table = 'SystemNode';

    /**
     * 显示节点列表
     */
    public function index()
    {
        $nodes = ToolsService::arr2table(NodeService::get(), 'node', 'pnode');
        $groups = [];
        foreach ($nodes as $node) {
            $pnode = explode('/', $node['node'])[0];
            if ($node['node'] === $pnode) {
                $groups[$pnode]['node'] = $node;
            }
            $groups[$pnode]['list'][] = $node;
        }
        unset($groups['super']);
        return $this->fetch('', ['title' => '系统节点管理', 'nodes' => $nodes, 'groups' => $groups]);
        // return view('', ['title' => '系统节点管理', 'nodes' => $nodes, 'alert' => $alert]);
    }
    
    /**
     * 清理无效的节点记录
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function clear()
    {
        $nodes = array_keys(NodeService::get());
       
        if (false !== Db::name($this->table)->where('type',1)->whereNotIn('node', $nodes)->delete()) {
            $this->success('清理无效节点记录成功！', '');
        }
        $this->error('清理无效记录失败，请稍候再试！');
    }

    /**
     * 保存节点变更
     */
    /*public function save()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if (isset($post['name']) && isset($post['value'])) {
                $nameattr = explode('.', $post['name']);
                $field = array_shift($nameattr);
                $data = ['node' => join(',', $nameattr), $field => $post['value']];
                DataService::save($this->table, $data, 'node');
                $this->success('参数保存成功！', '');
            }
        } else {
            $this->error('访问异常，请重新进入...');
        }
    }*/
    
    /**
     * 保存节点变更
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save()
    {
        if ($this->request->isPost()) {
            list($post, $data) = [$this->request->post(), []];
            foreach ($post['list'] as $vo) {
                if (!empty($vo['node'])) {
                    $data['node'] = $vo['node'];
                    $data[$vo['name']] = $vo['value'];
                }
            }
            !empty($data) && DataService::save($this->table, $data, 'node');
            $this->success('参数保存成功！', '');
        }
        $this->error('访问异常，请重新进入...');
    }

}

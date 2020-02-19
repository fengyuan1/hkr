<?php

namespace controller;

use service\DataService;
use service\ToolsService;
use think\Config;
use think\Controller;
use think\Db;
use think\db\Query;

/**
 * 后台权限基础控制器
 * Class BasicAdmin
 * @package controller
 */
class BasicAdmin extends Controller
{

    /**
     * 页面标题
     * @var string
     */
    public $title;

    /**
     * 默认操作数据表
     * @var string
     */
    public $table;

    /**
     * 默认检查用户登录状态
     * @var bool
     */
    public $checkLogin = true;

    /**
     * 默认检查节点访问权限
     * @var bool
     */
    public $checkAuth = true;

    /**
     * 表单默认操作
     * @param Query $dbQuery 数据库查询对象
     * @param string $tplFile 显示模板名字
     * @param string $pkField 更新主键规则
     * @param array $where 查询规则
     * @param array $extendData 扩展数据
     * @return array|string
     */
    protected function _form($dbQuery = null, $tplFile = '', $pkField = '', $where = [], $extendData = [], $url = "")
    {
        $db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
        $pk = empty($pkField) ? ($db->getPk() ? $db->getPk() : 'id') : $pkField;
        $pkValue = $this->request->request($pk,
            isset($where[$pk]) ? $where[$pk] : (isset($extendData[$pk]) ? $extendData[$pk] : null));

        // 非POST请求, 获取数据并显示表单页面
        if (!$this->request->isPost()) {
            if ($pkValue !== null) {
                $vo = array_merge((array)$db->where($pk, $pkValue)->where($where)->find(), $extendData);
            } else {
                $vo = $extendData;
            }

            if (false !== $this->_callback('_form_filter', $vo)) {
                empty($this->title) || $this->assign('title', $this->title);
                // $data = $this->merchantlist(1);
                // return $this->fetch($tplFile, ['vo' => $vo, "data" => $data]);
                return $this->fetch($tplFile, ['vo' => $vo]);
            }
            return $vo;
        }

        // POST请求, 数据自动存库
        $data = array_merge($this->request->post(), $extendData);
        if (false !== $this->_callback('_form_filter', $data)) {

            if (!isset($data['id'])) {

                if (isset($data['start_time']) || isset($data['end_time'])) {
                    // $data['start_time'] = strtotime($data['start_time']);
                    // $data['end_time'] = strtotime($data['end_time']);
                } else {
                    if(!in_array($db->getTable(),['system_auth','system_menu'])){
                        $data['time'] = time();
                    }
                }
            }

            $result = DataService::save($db, $data, $pk, $where, $dbQuery);

            if (false !== $this->_callback('_form_result', $result)) {
                if ($result !== false) {
                    $this->success(lang('操作成功'), $url);
                } else {
                    $this->error(lang('操作失败，请稍候再试'));
                }
            }
        }
    }

    /**
     * 列表集成处理方法
     * @param Query $dbQuery 数据库查询对象
     * @param bool $isPage 是启用分页
     * @param bool $isDisplay 是否直接输出显示
     * @param bool $total 总记录数
     * @return array|string
     */
    protected function _list($dbQuery = null, $isPage = true, $isDisplay = true, $total = false)
    {
        $db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
        // 列表排序默认处理
        if ($this->request->isPost() && $this->request->post('action') === 'resort') {
            $data = $this->request->post();
            unset($data['action']);
            foreach ($data as $key => &$value) {
                if (false === $db->where('id', intval(ltrim($key, '_')))->setField('sort', $value)) {
                    $this->error(lang('列表排序失败, 请稍候再试'));
                }
            }
            $this->success(lang('列表排序成功, 正在刷新列表'), '');
        }
        // 列表数据查询与显示
        if (null === $db->getOptions('order')) {
            $fields = $db->getTableFields($db->getTable());
            in_array('sort', $fields) && $db->order('sort asc');
        }
        $result = [];
        if ($isPage) {
//            $rowPage = intval($this->request->get('rows', cookie('rows')));
//            cookie('rows', $rowPage >= 10 ? $rowPage : 20);
            $rowPage = intval($this->request->param('rows', Config::get('paginate.list_rows')));
            $page = $db->paginate($rowPage, $total, ['query' => $this->getUrlQuery()]);
            $result['list'] = $page->all();
            $result['page'] = preg_replace(
                ['|href="(.*?)"|', '|pagination|'],
                ['data-open="$1" href="javascript:void(0);"', 'pagination pull-right'],
                $page->render()
            );
        } else {
            $result['list'] = $db->select();
        }
        if (false !== $this->_callback('_data_filter', $result['list']) && $isDisplay) {
            !empty($this->title) && $this->assign('title', $this->title);

            return $this->fetch('', $result);
        }

        return $result;
    }


    private function getUrlQuery()
    {
        $url_array = parse_url($this->request->url());
        $query_string = isset($url_array['query']) ? $url_array['query'] : '';
        parse_str($query_string, $result);
        return $result;
    }


    /**
     * 当前对象回调成员方法
     * @param string $method
     * @param array|bool $data
     * @return bool
     */
    protected function _callback($method, &$data)
    {
        foreach ([$method, "_" . $this->request->action() . "{$method}"] as $_method) {
            if (method_exists($this, $_method) && false === $this->$_method($data)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 分级显示所能看到的数据
     * @param $merchant_id
     * 登录者所属的商户ID
     * @param $key
     * 用于查询的字段
     * @return int
     */
    protected function gradinglist($agent_id, $key)
    {
        // 隐藏代理商条件查询
        return $map = 0;

        if ($merchant_id == 1) {
            return $map = 0;
        } else {
            if (db('med_agent')->where('pid', $agent_id)->find()) {
                $data = db('med_agent')->select();
                $mo = array();
                foreach ($data as $k => $v) {
                    if ($data[$k]['pid'] == $merchant_id) {
                        $mo[$k] = $data[$k]['id'];
                    }
                    foreach ($mo as $items) {
                        if ($items == $data[$k]['pid']) {
                            $mo[$k] = $data[$k]['id'];
                        }
                    }
                }
                array_push($mo, $merchant_id);
                $k = 0;
                foreach ($mo as $item) {
                    $map[$key][$k] = ['=', $item];
                    $k++;
                }
                $map[$key][$k] = 'or';
                return $map;
            } else {
                $map[$key] = ['=', $merchant_id];
                return $map;
            }
        }
    }

    /**
     * 添加数据时   查看分级列表
     * @param $merchant_id
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     */
    protected function merchantlist($merchant_id)
    {
        if ($merchant_id == 1) {
            $data = db('med_equ_merchant')->select();
        } else {
            $map = $this->gradinglist($merchant_id, 'id');
            $data = db('med_equ_merchant')->where($map)->select();
        }
        foreach ($data as &$vo) {
            $vo['ids'] = join(',', ToolsService::getArrSubIds($data, $vo['id']));
        }
        $data = ToolsService::arr2table($data);
        return $data;
    }

    /**
     * 查看类别列表
     * @return false|\PDOStatement|string|\think\Collection
     */
    protected function typelist()
    {
        $data = db('mes_type')->where('is_deleted', 0)->select();
        return $data;
    }

    /**
     * AJAX返回值方法
     * @param $status
     * @param $reason
     * @param $data
     * @return mixed
     */
    protected function returnJson($status, $reason, $data)
    {
        $vio['status'] = $status; //状态码
        $vio['reason'] = $reason; //错误信息
        $vio['data'] = $data; //返回信息
        return json_encode($vio);
    }

    /**
     * 判断该分类是否存在子分类
     * @param $id  分类id
     * @return  bool
     */
    protected function getSonTree($id)
    {
        $res = Db::name("med_equ_merchant")->where("pid", "$id")->select();
        if ($res) {
            //存在子分类
            return true;
        }
        //不存在子分类
        return false;
    }
}
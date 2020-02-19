<?php

namespace controller;

use think\Config;
use think\Controller;


class BasicAdmin2 extends Controller
{
    public $checkLogin = true;
    public $checkAuth = true;


    protected function validateInput($validate)
    {
        if (!$validate->check($this->request->param())) {
            $this->error($validate->getError());
        }
    }


    protected function resultMessage($is_success, $success_msg = null, $error_msg = null)
    {
        if ($success_msg === null) {
            $success_msg = '操作成功';
        }
        if ($error_msg === null) {
            $error_msg = '操作失败，请稍候再试';
        }

        if ($is_success) {
            $this->success($success_msg, '');
        } else {
            $this->error($error_msg);
        }
    }


    protected function viewPaginated($db_query, $tpl_file = '', $total = false)
    {
        $rows = Config::get('paginate.list_rows');
        $url_query = $this->getUrlQuery();

        $paginator = $db_query->paginate($rows, $total, ['query' => $url_query]);

        $page_html = $paginator->render();
        $page_html = preg_replace('|class="pagination"|', 'class="pagination pull-right"', $page_html);
        $page_html = preg_replace('|href="(.*?)"|', 'data-open="$1" href="javascript:void(0);"', $page_html);

        return $this->view->fetch($tpl_file, [
            'list' => $paginator->items(),
            'page' => $page_html,
            'paginate' => ['page' => $paginator->currentPage(), 'rows' => $rows]
        ]);
    }


    private function getUrlQuery()
    {
        $url_array = parse_url($this->request->url());
        $query_string = isset($url_array['query']) ? $url_array['query'] : '';
        parse_str($query_string, $result);
        return $result;
    }
}


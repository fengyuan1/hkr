<?php

namespace library;


class DbPaginator
{
    private $db;

    private $callback;


    public function __construct($db)
    {
        $this->db = $db;
    }


    public function each($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * 分页查询
     * @param int|array $listRows 每页数量 数组表示配置参数
     * @param int|bool  $simple   是否简洁模式或者总记录数
     * @param array     $config   配置参数
     *                            page:当前页,
     *                            path:url路径,
     *                            query:url额外参数,
     *                            fragment:url锚点,
     *                            var_page:分页变量,
     *                            list_rows:每页数量
     *                            type:分页类名
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function paginate($listRows = null, $simple = false, $config = [])
    {
        $paginator = $this->db->paginate($listRows, $simple, $config);
        if ($this->callback !== null) {
            $paginator = $paginator->each($this->callback);
        }
        return $paginator;
    }
}
<?php

 // +----------------------------------------------------------------------
 // | 会话设置
 // +----------------------------------------------------------------------
 
/* 定义会话路径 */
$_path_ = RUNTIME_PATH . 'sess' . DIRECTORY_SEPARATOR;
file_exists($_path_) || mkdir($_path_, 0755, true);


return [
	// 'id'             => '',
    // SESSION_ID的提交变量,解决flash上传跨域
    // 'var_session_id' => '',
    // SESSION 前缀
    'prefix'         => 'enyue',
    // 驱动方式 支持redis memcache memcached
    'type'           => '',
    // 保存路径
    'path'          => $_path_,
    // 是否自动开启 SESSION
    'auto_start'     => true,
    // 默认session有效期
    // 'expire' => 7200
];
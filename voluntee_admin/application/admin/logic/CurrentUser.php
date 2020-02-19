<?php

namespace app\admin\logic;

use think\Session;
use think\Config;

/**
 * 当前登录的用户
 */
class CurrentUser
{
    /**
     * 当前用户id
     * @return int
     */
    public static function getId()
    {
        return (int)Session::get('user.id');
    }

    /**
     * 当前用户所属的代理商或运营方id
     * @return int
     */
    public static function getAgentId()
    {
        return (int)Session::get('user.agent_id');
    }

    /**
     * 当前用户是否属于运营方
     * @return bool
     */
    public static function isRootAgent()
    {
        return (self::getAgentId() == Config::get('auth.root_agent_id')) ? true : false;
    }
}
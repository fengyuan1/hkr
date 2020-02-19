<?php

namespace library;

use think\Cache;
use think\Session;


class Login
{
    private static $prefix = 'admin_login_expire_';


    public static function setExpire($seconds)
    {
        $session_id = md5(implode('', [(string)time(), sprintf('%04d', rand(0, 9999))]));
        Session::set('session_id', $session_id);
        Cache::set(self::$prefix . $session_id, 'login_expire', $seconds);
    }


    public static function isValid()
    {
        $is_valid = Session::has('session_id') && Cache::has(self::$prefix . Session::get('session_id'));
        if (!$is_valid) {
            Session::destroy();
        }
        return $is_valid;
    }
}
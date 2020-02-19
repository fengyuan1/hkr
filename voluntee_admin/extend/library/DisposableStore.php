<?php

namespace library;

use think\Cache;


class DisposableStore
{
    private static $prefix = 'admin_disposable';


    public static function set($content)
    {
        $token = uniqid();
        Cache::set(self::$prefix . $token, $content, 60);
        return $token;
    }


    public static function get($token)
    {
        $content = Cache::get(self::$prefix . $token, false);
        if ($content !== false) {
            Cache::rm(self::$prefix . $token);
        }
        return $content;
    }
}
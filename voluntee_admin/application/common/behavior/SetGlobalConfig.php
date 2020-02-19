<?php


namespace app\common\behavior;

use think\Config;

class SetGlobalConfig 
{
    public function run(&$params)
    {
    	$path = CONF_PATH . 'global' . DS . 'config.php';
        if (file_exists($path)) {
            $config = include $path;
            foreach ($config as $key => $value) {
            	if($key == 'redis'){
            		Config::set('cache.host',$value['host']);
            		Config::set('cache.port',$value['port']);
            		continue;
            	}
            	if(is_array($value)){
		            foreach ($value as $k=> $v) {
		                Config::set($key . '.' . $k, $v);
		            }
		        }else{
		            Config::set($key,$value);
		        }
            }          	
        }
    }
}
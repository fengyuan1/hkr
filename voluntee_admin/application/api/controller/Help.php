<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/19
 * Time: 11:44
 */

namespace app\api\controller;


use think\Db;
use think\Validate;

class Help extends Base
{
    //帮助中心
    public function help_detail(){

        $help_id=input('help_id');
        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'help_id' => 'require',
        ]));

        $result=Db::table('help_detail')->where('help_detail',$help_id)->select();
        return outputSuccess($result);
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/17
 * Time: 11:17
 */

namespace app\api\validate;


use think\Validate;

class Login extends Validate
{
    protected $rule = [
        'openid'  =>  'require',
        'code'  =>  'require',
        'phone'=> 'max:11'

    ];

}
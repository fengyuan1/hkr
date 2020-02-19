<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/17
 * Time: 16:27
 */

namespace app\api\validate;


use think\Validate;

class Base extends Validate
{
    protected $rule = [
        'openid'  =>  'require',
    ];

}
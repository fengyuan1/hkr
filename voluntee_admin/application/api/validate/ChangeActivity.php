<?php
/**
 * Created by PhpStorm.
 * User: iphoneyuan
 * Date: 2020/1/27
 * Time: 20:42
 */

namespace app\api\validate;


use think\Validate;

class ChangeActivity extends Validate
{
    protected $rule = [
        'title'  =>  'require',
        'area_code'  =>  'require',
        'activity_type_id'  =>  'require',
        'linkman'  =>  'require',
        'begin_time'  =>  'require',
        'end_time'  =>  'require',
        'address'  =>  'require',
        'phone'  =>  'require|max:11',
        'content'  =>  'require',
        'annotation'  =>  'require',
        'activity_id' =>'require'
    ];

}
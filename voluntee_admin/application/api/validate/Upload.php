<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/18
 * Time: 15:41
 */

namespace app\api\validate;


use think\Validate;

class Upload extends Validate
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
    ];
}
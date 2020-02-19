<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/19
 * Time: 17:58
 */

namespace app\api\controller;


use think\Db;
use think\Validate;

class Like extends Base
{
    //活动点赞操作
    public function like(){
    $openid=input('opneid');
    $activity_id=input('activity_id');

    $user_message=self::get_user_message($openid);
    /** 参数验证 **/
    $this->validateInput(Validate::make([
        'activity_id' => 'require',
    ])
    );

    /** 查询是否已经点赞 **/
    $check_like=Db::table('activity_like')->where('activity_id',$activity_id)->where('user_id',$user_message['id'])->count();

    if(!$check_like){
        /** 活动点赞数目+1 **/
        Db::table('activity_detail')->where('activity_id',$activity_id)->setInc('like_num',1);
        //记录
        Db::table('activity_like')->insert([
            'activity_id'=>$activity_id,
            'user_id'=>$user_message['id']
        ]);
    }else{
        return outputError('请勿重复点赞');
    }

    /** 人员成长积分 **/
    $this->add_integration($openid,8);
    //查询该活动
    $activity_user_id=Db::table('activity')->where('id',$activity_id)->value('area_user_id');
    $this->add_integration($activity_user_id,9);
    return outputSuccess('点赞成功');
    }

}
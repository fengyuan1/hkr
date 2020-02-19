<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/19
 * Time: 14:29
 */

namespace app\api\controller;

//评论

use think\Db;
use think\Validate;

class Commend extends Base
{
    //获取评论评价
  public function get_commend(){

    $openid=input('openid');
    $activity_id=input('activity_id');

      /** 参数验证 **/
      $this->validateInput(Validate::make([
          'activity_id' => 'require',
      ])
      );

    $user_message=self::get_user_message($openid);
    $data=[];
    $array=[];
    //找到该活动的发布人
    $area_user_id=Db::table('activity')->where('id',$activity_id)->value('area_user_id');

    $result=Db::table('activity_comment')->alias('a')
        ->join('user b','a.user_id =b.id')
        ->where('activity_id',$activity_id)
        ->where('a.is_check',1)
        ->field('a.user_id,a.content,a.img,b.truename,b.avatarUrl')
        ->select();

    foreach ($result as $kk=>$vo){
        $result[$kk]['image']=json_decode($result[$kk]['img'],true);
        if($vo['user_id']==$area_user_id){
            $data= $result[$kk];
            unset($result[$kk]);
        }
    }

    if(count($data)){
        $array[0]=$data;
        unset($array[0]['img']);
    }

     foreach ($result as $k=>$v){
         unset($v['img']);
         $array[]=$v;

     }

    return outputSuccess($array);
  }

  //上传评论
  public function up_commend(){

  }


}
<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/16
 * Time: 20:15
 */

namespace app\api\controller;


use think\Db;
use think\Loader;
use think\Session;


class Login extends Base
{
    public function login(){
      $openid=input('openid');
      $phone=input('phone');
      $nickname=input('nickname');
      $code=input('code');

      $data=[
          'openid'=>$openid,
          'phone'=>$phone,
          'nickname'=>$nickname,
          'code'=>$code
      ];
     $validate = Loader::validate('Login');

     if(!$validate->check($data)){
         return outputError($validate->getError());
     }

    /** 查询数据库是否存在该openid**/
    if(Db::table('user')->where('openid',$openid)->find()){
    //若存在,则更新一下地域code
     Db::table('user')->where('openid',$openid)->update(['area_code'=>$code,'is_lock'=>1]);
    }else{
    //若不存在
        if(!empty($nickname)&&!empty($phone)){
            Db::table('user')->insert(['nickname'=>$nickname,'openid'=>$openid,'area_code'=>$code,'phone'=>$phone,'is_lock'=>1]);
        }else{
            return outputSuccess('缺少手机号以及昵称字段');
        }
    }
    $data=['message'=>'绑定成功','lock_time'=>date("Y-m-d H:i:s",time())];

    return outputSuccess($data);

    }

}
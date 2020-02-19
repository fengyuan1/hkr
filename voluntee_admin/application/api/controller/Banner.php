<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/15
 * Time: 9:39
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Banner extends Base
{
    //获取
    public function index(){
      $arr=Db::table('banner')->select();

      if(count($arr)>0){
       foreach ($arr as $kk=>$vo){
           //获取当前域名
              $arr[$kk]['image']=$this->joint_domain_pre($vo['image']);
       }
      }
      return outputSuccess($arr);
    }

}
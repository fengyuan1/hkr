<?php
/**
 * Created by PhpStorm.
 * User: iphoneyuan
 * Date: 2020/1/27
 * Time: 16:38
 */

namespace app\api\controller;


use think\Db;

class Organ extends Base
{
    //
    public function index(){
      $result=Db::table('organ')->select();
      return outputSuccess($result);
    }

}
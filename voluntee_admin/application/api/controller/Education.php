<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/15
 * Time: 15:51
 */

namespace app\api\controller;


use think\Db;
use think\Loader;


class Education extends Base
{

    //线上学习详情
    public function up_learn_detail(){
      $edu_content_id=input('edu_content_id');
      $openid=input('openid');

     /** 根据线上学习列表查询详情 **/
       $result=Db::table('up_edu_content_detail')->where('edu_content_id',$edu_content_id)->find();

      return outputSuccess($result);
    }



}
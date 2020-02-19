<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/15
 * Time: 9:26
 */

namespace app\api\controller;

use think\Validate;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Session;


class Base extends Controller
{
    protected $beforeActionList = [
        'sign',
        'checeklogin'
    ];

     //签名
     public function sign(){
         $sign=input('sign');

         $key='volunteer';

         if($sign!=md5($key)){
             return outputError('签名失败');
         }
     }

     //验证登录
    public function checeklogin(){
         if(Request()->controller()!='Login'&&Request()->controller()!='Recommend') {
             $openid = input('openid');
            /** 验证器，必须传入openid **/
             $validate = Loader::validate('Base');
              $data['openid']=$openid;
             if(!$validate->check($data)){
                 return outputError($validate->getError());
             }
             /** 验证是否已绑定 **/
             $user=self::get_user_message($openid);
             if (!$user['is_lock']) {
                 return outputError('该用户尚未绑定');
             }
         }
    }

     //
    public function joint_domain_pre($url){

        $request = Request()->domain();

        return $request.'/public/static/'.$url;
    }

    //获取用户信息
    public function get_user_message($openid){
        $result=Db::table('user')->where('openid',$openid)->find();
        return $result;
    }

   //根据参加不同的活动进行积分奖励
    public function add_integration($openid,$energy_category_id){

       $user_message=self::get_user_message($openid);

       //查询所需积分
       $integration=Db::table('energy_category')->where('id',$energy_category_id)->value('integration');

       //增加会员本身的积分
       Db::table('user')->where('id',$user_message['id'])->setInc('integration',$integration);

       //更新会员等级
        $user=Db::table('user')->alias('a')
            ->join('level_type b','a.level_id=b.id')
            ->field('a.integration as user_integration,b.integration as need_integration')
            ->find();

        if($user['user_integration']>$user['need_integration']){
          //获取相应的等级
           $arrive_type=Db::table('level_type')
               ->where('integration','<=',$user['user_integration'])
               ->order('integration desc')
               ->find();

            Db::table('user')->where('id',$user_message['id'])->update(['level_id'=>$arrive_type['id']]);
        }

       //插入记录表
       Db::table('energy_record')->insert([
         'energy_category_id'=>$energy_category_id,
         'user_id'=>$user_message['id'],
         'integration'=>$integration,
         'create_time'=>date('Y-m-d H:i:s',time())
       ]);



    }


    /**
     * 验证传入参数
     * @param Validate $validate
     */
    protected function validateInput($validate)
    {
        $this->validateData($validate, $this->request->param());
    }

    /**
     * 验证数据
     * @param array $data
     * @param Validate $validate
     */
    protected function validateData($validate, $data)
    {
        if (!$validate->check($data)) {
           return outputError($validate->getError());
        }
    }

}
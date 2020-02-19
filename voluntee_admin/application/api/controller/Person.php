<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/17
 * Time: 17:08
 */

namespace app\api\controller;


use think\Db;
use think\Validate;

class Person extends Base
{
    //我的个人信息
    public function my_message(){
      $openid=input('openid');
      $area_second_name='';
      $area_first_name='';
      $user_message=self::get_user_message($openid);
      $user_message['level_type']=Db::table('level_type')->where('id',$user_message['level_id'])->value('level_name');
      //查询所属区域名称
      $area_third_name=Db::table('wy_area')->where('code',$user_message['area_code'])->value('name');
      $area_second_id=Db::table('wy_area')->where('code',$user_message['area_code'])->value('tid');
      if($area_second_id){
          $area_second=Db::table('wy_area')->where('id',$area_second_id)->field('id,name')->find();
          $area_second_name=$area_second['name'];
          if($area_second){
              $area_first_name=Db::table('wy_area')->where('id',$area_second['id'])->value('name');
          }
      }
      $user_message['area_name']=$area_first_name.$area_second_name.$area_third_name;
      unset($user_message['openid']);
      unset($user_message['emergency_man']);
      unset($user_message['is_check']);
      unset($user_message['is_lock']);
      unset($user_message['emergency_phone']);
      unset($user_message['system_user_id']);
      unset($user_message['idcard']);
      unset($user_message['level_id']);
      return outputSuccess($user_message);
    }

    //修改个人信息
    public function change_message(){
        $openid=input('openid');
        $avatarUrl=input('avatarUrl');
        $truename=input('truename');
        $belong_organ=input('belong_organ');
        $code=input('code');
        $phone=input('phone');

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'avatarUrl' => 'require',
            'truename' => 'require',
            'belong_organ' => 'require',
            'code' => 'require',
            'phone'=>'require|max:11'
        ])
        );

        $result=Db::table('user')->where('openid',$openid)->update([
            'avatarUrl,'=>$avatarUrl,
            'truename'=>$truename,
            'belong_organ'=>$belong_organ,
            'code'=>$code,
            'phone'=>$phone,
        ]);
        return outputSuccess('修改成功');
    }

    //义工认证
    public function vol_cert(){
        $openid=input('openid');
        $truename=input('truename');
        $nickname=input('nicknme');
        $sex=input('sex');
        $idcard=input('idcard');
        $phone=input('phone');
        $emergency_man=input('emergency_man');
        $belong_organ=input('belong_organ');

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'avatarUrl' => 'require',
            'truename' => 'require',
            'nicknme' => 'require',
            'sex' => 'require|num',
            'idcard'=>'require|max:11',
            'phone' => 'require',
            'emergency_man' => 'require',
        ])
        );

        $result=Db::table('user')
            ->where('openid',$openid)
            ->update([
            'truename'=>$truename,
            'nicknme'=>$nickname,
            'sex'=>$sex,
            'idcard'=>$idcard,
            'phone'=>$phone,
            'emergency_man'=>$emergency_man,
             'belong_organ'=>$belong_organ
        ]);

        return outputSuccess('认证成功');

    }


    //线下课程管理
    public function  my_content_manage(){
        $openid=input('openid');
        $user_message=self::get_user_message($openid);
        $content_status=input('content_status');  //-1 未开始  0为正在进行   1为已完成

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'content_status' => 'require',
        ])
        );

        $result=Db::table('activity_user')->alias('a')
            ->join('activity b','a.activity_id=b.id')
            ->where('a.user_id',$user_message['id'])
            ->where('b.activity_type_id',3)
            ->field('a.activity_id as content_id,b.title,b.img,begin_time,end_time');
        if($content_status=='-1'){
            $result=$result->where('b.end_time','>',time())
                ->where('b.begin_time','>',time())
                ->select();
        }else if($content_status=='0'){
            $result=$result
                ->where('b.begin_time','<',time())
                ->where('b.end_time','>',time())
                ->select();
        }else if($content_status=='1'){
            $result=$result->where('b.begin_time','<',time())
                ->where('b.end_time','<',time())
                ->select();
        }
        foreach ($result as $k=>$v){
            $result[$k]['begin_time']=date('Y-m-d H:i:s',$result[$k]['begin_time']);
            $result[$k]['end_time']=date('Y-m-d H:i:s',$result[$k]['end_time']);
            $result[$k]['img']=$this->joint_domain_pre($result[$k]['img']);
        }
        return outputSuccess($result);
    }

    //我的发布
    public function my_upload_activity(){
        $openid=input('openid');
        $user_message=self::get_user_message($openid);
        /** 判断其是否为区域负责人 **/
        if($user_message['system_user_id']==''){
            return outputError('您尚未成为区域负责人，尚无权限访问该模块');
        }
        $result=Db::table('activity')
              ->where('area_user_id',$user_message['id'])
              ->field('linkman,phone,title,address,FROM_UNIXTIME(begin_time,\'%Y-%m-%d %H:%i:%s\') as begin_time,FROM_UNIXTIME(end_time,\'%Y-%m-%d %H:%i:%s\') as end_time')
              ->select();
        return outputSuccess($result);
    }

    //活动管理
    public function my_activity_manage(){
        $openid=input('openid');
        $user_message=self::get_user_message($openid);
        $activity_status=input('activity_status');  //-1 未开始  0为正在进行   1为已完成

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'activity_status' => 'require',
        ])
        );

       /** 我说参与的活动**/
        $result=Db::table('activity_user')->alias('a')
            ->join('activity b','a.activity_id=b.id')
            ->where('a.user_id',$user_message['id'])
            ->where('b.activity_type_id','<>',3)
            ->field('a.activity_id,b.img,b.title,b.linkman,b.address,b.begin_time,b.end_time');

        if($activity_status=='-1'){
            $result=$result->where('b.end_time','>',time())
                ->where('b.begin_time','>',time())
                ->select();
        }else if($activity_status=='0'){
            $result=$result
                ->where('b.begin_time','<',time())
                ->where('b.end_time','>',time())
                ->select();
        }else if($activity_status=='1'){
            $result=$result->where('b.end_time','<',time())
                ->where('b.begin_time','<',time())
                ->select();
        }
        foreach ($result as $k=>$v){
            $result[$k]['begin_time']=date('Y-m-d H:i:s',$result[$k]['begin_time']);
            $result[$k]['end_time']=date('Y-m-d H:i:s',$result[$k]['end_time']);
            $result[$k]['img']=$this->joint_domain_pre($result[$k]['img']);
        }
        return outputSuccess($result);
    }

    //我的能量-能量明细
    public function my_energy(){
        $openid=input('openid');
        $user_message=self::get_user_message($openid);

        $result=Db::table('energy_record')->alias('a')
            ->join('energy_category b','a.energy_category_id=b.id')
            ->where('user_id',$user_message['id'])
            ->field('energy_category_id,integration,create_time，category_name')
            ->select();

        return outputSuccess($result);

    }

    //我的能量-兑换明细
    public function my_exchange(){
        $openid=input('openid');
        $user_message=self::get_user_message($openid);

         $result=Db::table('exchange')->alias('a')
             ->join('user b','a.user_id=b.id')
             ->join('award c','a.award_id=c.id')
             ->where('user_id',$user_message['id'])
             ->field('b.truename,a.integration,b.address,b.phone,a.create_time')
             ->select();

         return outputSuccess($result);

    }

    //帮助中心
    public function help(){
        $result=Db::table('help_list')->select();
        return outputSuccess('$result');
    }


}
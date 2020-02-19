<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/17
 * Time: 16:18
 */

namespace app\api\controller;


use think\Db;
use think\Loader;
use think\Validate;

class Activity extends Base
{
    //活动详情
    public function activity_detail(){
     $flag=false;
     $openid = input('openid');
     $activity_id=input('activity_id');

    /** 参数验证 **/
    $this->validateInput(Validate::make([
        'activity_id' => 'require',
    ])
    );
    /** 获取该用户的地区列表 **/
    $user_message= self::get_user_message($openid);

     /** 查找相对应的活动 **/
     $activity=Db::table('activity')->alias('a')
         ->join('user_area b','a.area_user_id=b.user_id')
         ->where('a.id',$activity_id)
         ->select();

      if(empty($activity)){
          return outputError('找不到该活动');
      }
      /** 查找相对应的活动 **/

     /** 查找活动相对应的人员 **/
     $activity_user=Db::table('activity_user')->alias('a')
         ->join('user b','a.user_id=b.id')
         ->field('b.avatarUrl,b.truename')
         ->where('a.activity_id',$activity_id)
         ->select();
     /** 查找活动相对应的人员 **/


    /** 判断是否有权限查看该活动 **/
        $flag= $this->check_authority($user_message,$activity,$flag);
    /** 判断是否有权限查看该活动 **/
        if(!$flag){
            return outputError('无权限查看该活动');
        }
    /** 查询该活动 **/
    $activity_data=Db::table('activity')->alias('a')
        ->join('activity_detail b','a.id=b.activity_id')
        ->where('a.id',$activity_id)
        ->find();


    $activity_data['begin_time']=date('Y-m-d H:i:s', $activity_data['begin_time']);
    $activity_data['end_time']=date('Y-m-d H:i:s', $activity_data['end_time']);


    $activity_data['activity_user']=$activity_user;

    /** 查询该活动 **/
     if($flag){
         return outputSuccess($activity_data);
     }else{
         return outputError('该用户无权限访问该活动');
      }
    }

    //报名
    public function sign_up(){
        $flag=false;
        $openid = input('openid');
        $activity_id=input('activity_id');
        /** 验证是否已实名并通过审核**/
        $check_status=Db::table('user')->where('openid',$openid)->value('is_check');
        if($check_status==1){//状态为1，继续下面操作
            /** 查询该义工有无权限报名该活动**/

            /** 获取该用户的信息 **/
            $user_message= self::get_user_message($openid);

            /** 查找相对应的活动 **/
            $activity=Db::table('activity')->alias('a')
                ->join('user_area b','a.area_user_id=b.user_id')
                ->where('a.id',$activity_id)
                ->select();
            if(empty($activity)){
                return outputError('找不到该活动');
            }
            //自身发布的活动不能报名
            $self=Db::table('activity')->where('id',$activity_id)->value('area_user_id');
            if($self==$user_message['id']){
                return outputError('不允许报名自身发布的活动');
            }

            /** 判断是否有权限报名该活动 **/
            $flag=$this->check_authority($user_message,$activity,$flag);
            /** 判断是否有权限报名该活动 **/
            if($flag){
                /** 不允许重复报名活动 **/
                if(Db::table('activity_user')->where('activity_id',$activity_id)->where('user_id',$user_message['id'])->count()){
                    return outputError('请勿重复报名活动');
                }
             $result=Db::table('activity_user')->insert(['create_time'=>date('Y-m-d h:i:s',time()),'activity_id'=>$activity_id,'user_id'=>$user_message['id'],'is_check'=>0]);

             $activity_data=Db::table('activity')->where('id',$activity_id)->find();
             $activity_data['begin_time']=date('Y-m-d H:i:s', $activity_data['begin_time']);
             $activity_data['end_time']=date('Y-m-d H:i:s', $activity_data['end_time']);
             $data=['activity_data'=>$activity_data,'message'=>'报名成功'];
              return outputSuccess($data);
            }else{
                return outputError('该用户无权限报名该活动');
            }
            /** 查询该义工有无权限报名该活动**/


        }else if($check_status==-1){
            return outputError('很抱歉的通知您，您的信息没有通过审核，您无法参加报名活动');
        }else if($check_status==0){
            return outputError('很抱歉的通知您，请提交真实身份信息进行义工身份验证');
        }

    }

    //进行签到
    public function activity_sign(){
        $openid = input('openid');
        $activity_id=input('activity_id');
        $code=input('code');
        $user_message=self::get_user_message($openid);

        if(empty($activity_id)&&empty($code)){
         return outputError('参数不足');
        }
        //活动报名表中报名
        $sign_flag=Db::table('activity_user')->where('activity_id',$activity_id)->where('user_id',$user_message['id'])->count();

        /** 需要在用户区域内活动 **/
        if($sign_flag){

            /** 查找相对应的活动 **/
            $activity=Db::table('activity')
                ->where('id',$activity_id)
                ->find();
            if(empty($activity)){
                return outputError('找不到该活动');
            }
            /** 查找相对应的活动 **/
            /** 判断是否有权限报名该活动 **/
            if($activity['area_code']!=$code){
             return outputError('请移步至活动地区再进行签到');
            }
            /** 判断是否有权限报名该活动 **/

           $sign_count=Db::table('activity_sign')->where('activity_id',$activity_id)
                ->where('user_id',$user_message['id'])->count();
           if(!$sign_count) {
               Db::table('activity_sign')->insert([
                   'activity_id'=>$activity_id,
                   'user_id'=>$user_message['id'],
                   'is_sign' => 1,
                   'create_time' => date('Y-m-d H:i:s', time())
               ]);
           }else{
               return outputError('请勿重复签到');
           }

            /** 人员成长积分 **/
            $this->add_integration($openid,1);
            return outputSuccess('签到成功');

        }else{
            return outputError('您未参加该活动');
        }
    }
    //改变活动内容
    public function change_activity(){
        $openid = input('openid');
        $activity_id=input('activity_id');          //活动id
        $data['activity_id']=input('activity_id');
        $data['title']=input('title');             //主题
        $data['linkman']=input('linkman');         //联系人
        $data['address']=input('address');         //地址
        $data['phone']=input('phone');             //电话号码
        $data['content']=input('content');         //活动内容
        $data['annotation']=input('annotation');   //其他
        $data['longtitude']=input('longtitude');   //经度
        $data['latitude']=input('latitude');       //纬度
        $data['area_code']=input('code');          //地区代码
        $data['begin_time']=strtotime(input('begin_time'));   //开始时间
        $data['end_time']=strtotime(input('end_time'));       //结束时间
        $data['activity_type_id']=input('activity_type_id');  //活动类型
        $img=request()->file("img");

        //获取个人信息
        $user_message=self::get_user_message($openid);
        //数据验证
        $validate = Loader::validate('ChangeActivity');

        if(!$validate->check($data)){
            return outputError($validate->getError());
        }

        $message=Db::table('activity')->where('id',$activity_id)->find();
        //判断该活动是否开始
        $activity_begin_time=$message['begin_time'];
        $activity_end_time=$message['end_time'];
        if($activity_begin_time<=time()||$activity_end_time<=time()){
            return outputError('活动已开始，暂不允许修改');
        }

        //判断本人是否发布该活动
         if($user_message['id']!=$message['area_user_id']){
             return outputError('活动发布者与您的身份不符');
         }

        //修改该活动
        if($img){
            $info = $img->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'static/upload/');
            if($info){
                $a = $info->getSaveName();
                $imgp = str_replace("\\", "/", $a);
                $imgpath = 'static/upload/' . $imgp;

                /** 启动事务**/
                $activity_id = Db::table('activity')->where('id',$activity_id)->update([
                    'img' => $imgpath,
                    'title' => $data['title'],
                    'area_code' => $data['area_code'],
                    'activity_type_id' => $data['activity_type_id'],
                    'linkman' => $data['linkman'],
                    'begin_time' => $data['begin_time'],
                    'end_time' => $data['end_time'],
                    'create_time' => date('Y-m-d H:i:s', time()),
                    'update_time' => date('Y-m-d H:i:s', time()),
                    'address' => $data['address'],
                    'phone' => $data['phone'],
                    'longtitude'=>$data['longtitude'],
                    'latitude'=>$data['latitude']
                ]);
                $result = Db::table('activity_detail')->where('activity_id',$activity_id)->update([
                    'id'    =>$activity_id,
                    'title' => $data['title'],
                    'linkman' => $data['linkman'],
                    'begin_time' => $data['begin_time'],
                    'end_time' => $data['end_time'],
                    'create_time' => date('Y-m-d H:i:s', time()),
                    'update_time' => date('Y-m-d H:i:s', time()),
                    'content' => $data['content'],
                    'activity_id' => $activity_id,
                    'annotation' => $data['annotation'],
                ]);

                if(!$activity_id||!$result){
                    return outputError('修改失败');
                }


                //积分
                $this->add_integration($openid,2);

                return outputSuccess('活动修改成功');
            }else{
                return outputError('不支持该文件格式上传');
            }
        }else{
            return outputError('图片接收失败');
        }
    }

    //义工发布活动
    public function up_activity(){
        $openid = input('openid');
        $data['title']=input('title');             //主题
        $data['area_code']=input('code');          //地区代码
        $data['activity_type_id']=input('activity_type_id');  //活动类型
        $data['linkman']=input('linkman');         //联系人
        $data['begin_time']=strtotime(input('begin_time'));   //开始时间
        $data['end_time']=strtotime(input('end_time'));       //结束时间
        $data['address']=input('address');         //地址
        $data['phone']=input('phone');             //电话号码
        $data['content']=input('content');         //活动内容
        $data['annotation']=input('annotation');   //其他
        $data['longtitude']=input('longtitude');   //经度
        $data['latitude']=input('latitude');       //纬度
        $img=request()->file("img");
        $flag=false;
        /** 查询是否为区域负责人 **/
        $user_message= self::get_user_message($openid);
        if (empty($user_message['system_user_id'])){
          return outputError('您尚未成区域负责人，无权限');
        }

        /**进行数据校验**/
        $validate = Loader::validate('Upload');

        if(!$validate->check($data)){
            return outputError($validate->getError());
        }

       $flag=$this->up_check($user_message,$data['area_code'],$flag);
        if(!$flag){
           return outputError('暂无该地区权限发布该活动');
        }

        /** 上传图片操作  **/
        if($img){
            $info = $img->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'static/upload/');
            if($info){
                $a = $info->getSaveName();
                $imgp = str_replace("\\", "/", $a);
                $imgpath = 'static/upload/' . $imgp;

                /** 启动事务**/
                    $activity_id = Db::table('activity')->insertGetId([
                        'img' => $imgpath,
                        'title' => $data['title'],
                        'area_code' => $data['area_code'],
                        'activity_type_id' => $data['activity_type_id'],
                        'linkman' => $data['linkman'],
                        'begin_time' => $data['begin_time'],
                        'end_time' => $data['end_time'],
                        'create_time' => date('Y-m-d H:i:s', time()),
                        'update_time' => date('Y-m-d H:i:s', time()),
                        'address' => $data['address'],
                        'phone' => $data['phone'],
                        'area_user_id'=>$user_message['id'],
                        'longtitude'=>$data['longtitude'],
                        'latitude'=>$data['latitude']
                    ]);
                    $result = Db::table('activity_detail')->insert([
                        'id'    =>$activity_id,
                        'title' => $data['title'],
                        'linkman' => $data['linkman'],
                        'begin_time' => $data['begin_time'],
                        'end_time' => $data['end_time'],
                        'create_time' => date('Y-m-d H:i:s', time()),
                        'update_time' => date('Y-m-d H:i:s', time()),
                        'content' => $data['content'],
                        'activity_id' => $activity_id,
                        'annotation' => $data['annotation'],
                    ]);

                if(!$activity_id||!$result){
                    return outputError('入库失败');
                }


                //积分
                $this->add_integration($openid,2);

                return outputSuccess('活动发布成功');
            }else{
                return outputError('不支持该文件格式上传');
            }
        }else{
            return outputError('图片接收失败');
        }

    }
    public function up_check($user_message,$code,$flag){
        //判断
        $area=Db::table('user_area')->where('user_id',$user_message['id'])->select();
        foreach ($area as $kk=>$v ){
            if($v['wa_area_code']==$code){
                $flag=true;
            }
        }

        //第二步
        if(!$flag){
            $tid=Db::table('wy_area')->where('code',$code)->value('tid');
            $code_other=Db::table('wy_area')->where('id',$tid)->field('code')->select();

            foreach ($code_other as $a=>$b){
                $check_area=Db::table('user_area')->where('user_id',$user_message['id'])->where('wa_area_code',$b['code'])->count();
                if($check_area){
                    //第三步
                    $flag=true;
                }
            }
        }
        return $flag;

    }

    public function check_authority($user_message,$data,$flag){
        $area_user_id=0;
        /** 判断是否有权限报名该活动 **/
        //第一步
        foreach ($data as $k=>$vo){
            $area_user_id=$vo['area_user_id'];
            if($vo['wa_area_code']==$user_message['area_code']){
                $flag=true;
            }
        }

        //第二步
        if(!$flag){
            $tid=Db::table('wy_area')->where('code',$user_message['area_code'])->value('tid');
            $code=Db::table('wy_area')->where('id',$tid)->field('code')->select();

            foreach ($code as $a=>$b) {
                $check_area = Db::table('user_area')->where('user_id',$area_user_id)->where('wa_area_code', $b['code'])->count();

                if ($check_area) {
                    //第三步
                    $flag = true;
                }
            }
        }
        return $flag;
        /** 判断是否有权限报名该活动 **/
    }


}
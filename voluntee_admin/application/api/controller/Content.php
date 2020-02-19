<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/18
 * Time: 19:33
 */


namespace app\api\controller;

use think\Db;
use think\Validate;

class Content extends Base
{
    //获取线上课程详情
    public function  content_detail(){

        $openid=input('openid');
        $content_id=input('content_id');
        $category_id=input('category_id');

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'content_id' => 'require',
            'category_id'=>'require'
        ])
        );

        $user_message=self::get_user_message($openid);

        //判断其等级是否有阅读权限



        if($category_id==1){//文章
            //进行课程查询
            $content_detail=Db::table('up_edu_content_detail')
                ->where('edu_content_id',$content_id)
                ->field('title,content,read_num')
                ->find();
        }else if($category_id==2){//视频学习
            $content_detail=Db::table('up_edu_content_detail')
                ->where('edu_content_id',$content_id)
                ->field('title,video_url,read_num')
                ->find();
        }else{
            return outputError('尚无该类别');
        }

        //记录阅读数
        if(!Db::table('up_edu_content_read')->where('user_id',$user_message['id'])->where('content_id',$content_id)->count()){
            Db::table('up_edu_content_read')->insert([
                'content_id'=>$category_id,
                'user_id'=>$user_message['id']
            ]);
                Db::table('up_edu_content')->where('id',$content_id)->setInc('read_num',1);
            //积分增加
            if($category_id==1){//文章
                $this->add_integration($openid,5);
            }else{//视频学习
                $this->add_integration($openid,4);
            }
        }


        //进行课程阅读查询
        $people=Db::table('up_edu_content_read')->alias('a')
            ->join('user b','b.id=a.user_id')
            ->where('a.user_id',$user_message['id'])
            ->field('avatarUrl,truename')
            ->select();
        $content_detail['read_people']=$people;
        $content_detail['read_people_num']=count($people);
        return outputSuccess($content_detail);

    }

    //获取线上课程分类
    public function content_category(){
    $result=Db::table('up_edu_content_category')->select();
    return outputSuccess($result);

    }


}
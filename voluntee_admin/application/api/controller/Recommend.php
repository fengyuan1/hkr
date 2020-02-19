<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/16
 * Time: 8:30
 */

namespace app\api\controller;


use think\Db;
use think\Validate;
//公益活动推荐
class Recommend extends Base
{

    //活动搜索列表
    public function search_activity(){
        $title=input('title');
        $longtitude=input('longtitude');
        $latitude=input('latitude');

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'title' => 'require',
            'longtitude' => 'require|number',
            'latitude' => 'require|number',
        ])
        );
        $activity=Db::table('activity')
            ->where('begin_time','>',time())
            ->where('end_time','>',time())
            ->where('title','like','%'.$title.'%')
            ->field('img,title,id as activity_id,linkman,address,FROM_UNIXTIME(begin_time,\'%Y-%m-%d %H:%i:%s\') as begin_time,FROM_UNIXTIME(end_time,\'%Y-%m-%d %H:%i:%s\') as end_time,longtitude,latitude')
            ->select();

        foreach ($activity as $k=>$v){
            $activity[$k]['img']=$this->joint_domain_pre($activity[$k]['img']);
        }
        if($longtitude&&$latitude) {
            foreach ($activity as $k => $v) {
                $activity[$k]['distance'] = $this->getDistance($longtitude, $latitude, $activity[$k]['longtitude'], $activity[$k]['latitude']);
            }
            //排序
            $distance = array_column($activity, 'distance');
            array_multisort($distance, SORT_ASC, $activity);
        }
        return outputSuccess($activity);
    }

    //活动推荐列表
    public function get_comment_activity()
    {
       $openid=input('openid');
       $code=input('code');
       $longtitude=input('longtitude');
       $latitude=input('latitude');

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'code' => 'require',
            'longtitude' => 'require|number',
            'latitude' => 'require|number',
        ])
        );


        //如果已认证，则查询该用户区域内的活动
        if(!empty($openid)){
            $code=Db::table('user')->where('openid',$openid)->value('area_code');
        }

        $activity=Db::table('activity')
            ->where('area_code',$code)
            ->where('begin_time','>',time())
            ->where('end_time','>',time())
            ->where('activity_type_id',2)
            ->field('img,title,id as activity_id,longtitude,latitude')
            ->limit(4)
            ->select();
        foreach ($activity as $k=>$v){
        $activity[$k]['img']=$this->joint_domain_pre($activity[$k]['img']);
        }
        if($longtitude&&$latitude) {
            foreach ($activity as $k => $v) {
                $activity[$k]['distance'] = $this->getDistance($longtitude, $latitude, $activity[$k]['longtitude'], $activity[$k]['latitude']);
            }
            //排序
            $distance = array_column($activity, 'distance');
            array_multisort($distance, SORT_ASC, $activity);
        }
            return outputSuccess($activity);

    }

    //公益项目
    public function get_activity(){
        $openid=input('openid');
        $code=input('code');
        $activity_status=input('activity_status');

        if(empty($openid)&&empty($code)){
            return outputError('参数不足');
        }
        //如果已认证，则查询该用户区域内的活动
        if(!empty($openid)){
            $code=Db::table('user')->where('openid',$openid)->value('area_code');
        }

        /** 活动**/
        $result=Db::table('activity_user')->alias('a')
            ->join('activity b','a.activity_id=b.id')
            ->where('b.activity_type_id',2)
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

    //线上学习列表
    public function get_up_study_content(){
       $result=Db::table('up_edu_content')
           ->where('project_time','<= time',date('Y-m-d H:i:s',time()))
           ->where('status',0)
           ->select();
       return outputSuccess($result);
    }

    //线下学习列表
    public function get_down_study_activity(){
            $openid=input('openid');
            $code=input('code');
            $activity_status=input('activity_status');
            if(empty($activity_status)){
                return outputError('缺少activity_status');
            }
            if(empty($openid)&&empty($code)){
                return outputError('参数不足');
            }
            //如果已认证，则查询该用户区域内的活动
            if(!empty($openid)){
                $code=Db::table('user')->where('openid',$openid)->value('area_code');
            }

            /** 活动**/
            $result=Db::table('activity_user')->alias('a')
                ->join('activity b','a.activity_id=b.id')
                ->where('b.activity_type_id',3)
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


    //义工福利
    public function get_award(){
        $db=Db::table('award')
            ->field('id as award_id,title,begin_time,end_time,image,integration')
            ->where('stock','>',0)
            ->select();
        foreach ($db as $k=>$v) {
            $db[$k]['begin_time'] = date('Y-m-d H:i:s', $db[$k]['begin_time']);
            $db[$k]['end_time'] = date('Y-m-d H:i:s', $db[$k]['end_time']);
        }

        return outputSuccess($db);
    }

   //读书会
    public function get_read_activity()
    {
        $openid=input('openid');
        $code=input('code');
        $activity_status=input('activity_status');

        if(empty($openid)&&empty($code)){
            return outputError('参数不足');
        }
        //如果已认证，则查询该用户区域内的活动
        if(!empty($openid)){
            $code=Db::table('user')->where('openid',$openid)->value('area_code');
        }

        /** 活动**/
        $result=Db::table('activity_user')->alias('a')
            ->join('activity b','a.activity_id=b.id')
            ->where('b.activity_type_id',1)
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

   public function getDistance($lng1, $lat1, $lng2, $lat2) {  //经度 纬度  经度  纬度
       $EARTH_RADIUS = 6378.137;
       $radLat1 = $this->rad($lat1);
       $radLat2 = $this->rad($lat2);
       $a = $radLat1 - $radLat2;
       $b = $this->rad($lng1) - $this->rad($lng2);
       $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
       $s = $s * $EARTH_RADIUS;
       return $s;
 }
   public  function rad($d) {
         return $d * 3.1415926535898 / 180.0;
 }

}
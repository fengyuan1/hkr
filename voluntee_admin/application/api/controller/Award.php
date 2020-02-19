<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2020/1/19
 * Time: 9:46
 */

namespace app\api\controller;

use think\Validate;
use think\Db;

class Award extends Base
{
        //兑换接口
        public function exchange(){
        $data['award_id']=input('award_id');
        $data['openid']=input('openid');

        /** 参数验证 **/
        $this->validateInput(Validate::make([
            'award_id' => 'require',
           ])
        );
        $user_message=self::get_user_message( $data['openid']);

        /** 查询该奖品需要多少积分以及库存**/
        $need_interation=Db::table('award')->where('id',$data['award_id'])->value('integration');
        $stock=Db::table('award')->where('id',$data['award_id'])->value('stock');
        /** 扣除自己的积分**/
        //查询自己还有多少积分
        $integration=Db::table('user')->where('openid',$data['openid'])->value('integration');
        if(($need_interation<=$integration)&&$stock>0){
            $result=Db::table('user')->where('openid',$data['openid'])->setDec('integration', $need_interation);
            $award_a=Db::table('award')->where('id',$data['award_id'])->setDec('stock');
            if($result&&$award_a){
                /** 写入兑换表 **/
                Db::table('exchange')->insert(['award_id'=>$data['award_id'],
                    'integration'=>$need_interation,
                    'user_id'=>$user_message['id'],
                    'create_time'=>date('Y-m-d H:i:s',time())
                ]);
                return outputSuccess('恭喜您，兑换成功！');
            }else{
                return outputError('兑换失败!');
            }
        }else{
            return outputError('亲,您的积分不足');
        }
  }

}
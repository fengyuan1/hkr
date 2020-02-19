<?php
/**
 * Created by PhpStorm.
 * User: sinao
 * Date: 2019/5/25
 * Time: 16:21
 */

namespace app\common\controller;


use think\Controller;
use think\Db;
use think\Log as ThinkLog;
use think\Cache;

class Souqianba extends Controller
{
    private $config;

    public function _initialize() {
        $this->config = config('souqianba');
    }


//订单查询接口
    function query($terminal_sn, $terminal_key,$client_sn,$sn)
    {
        $url = $this->config['api_domain'] . '/upay/v2/query';

        $params['terminal_sn'] = $terminal_sn;           //收钱吧终端ID
        $params['sn']=$sn;              //收钱吧系统内部唯一订单号
        $params['client_sn'] = $client_sn;//商户系统订单号,必须在商户系统内唯一；且长度不超过64字节

        $ret = $this->pre_do_execute($params, $url, $terminal_sn, $terminal_key);

        return $ret;

    }

    //退款接口
    public function refund($order_num,$price){

        $url = $this->config['api_domain'] . '/upay/v2/refund';
        $params['terminal_sn'] = $this->config['terminal_sn'];           //收钱吧终端ID
        // $params['sn'] = '';              //收钱吧系统内部唯一订单号
        $params['client_sn']=$order_num;//商户系统订单号,必须在商户系统内唯一；且长度不超过64字节
        $params['refund_amount'] = (string) $price;                   //退款金额
        $params['refund_request_no'] = substr(time(),6,9);                 //商户退款所需序列号,表明是第几次退款
        $params['operator'] = 'admin';                    //门店操作员
        $terminal_key = Cache::get('terminal_key');
        // $terminal_key = '69a928e16e3d5552f8050ef48fba9ea7';
        if(empty($terminal_key)){
            $terminal_key = Db::name('system_config')->where('name','terminal_key')->value('value');
        }

        $ret = $this->pre_do_execute($params, $url,  $this->config['terminal_sn'], $terminal_key);
        ThinkLog::log('退款结果'.$ret);
        $result=json_decode($ret,true);
        // dump($params);
//         halt($result);
        if($result['biz_response']['data']['status']=='SUCCESS'){
            return true;
        }else{
            return $result['biz_response']['error_message'];
        }
    }

    //撤单接口
    public function revoke($order_num){
        $url = $this->config['api_domain'] . '/upay/v2/revoke';
        $params['terminal_sn'] = $this->config['terminal_sn'];           //收钱吧终端ID
        $params['sn']= '';              //收钱吧系统内部唯一订单号
        $params['client_sn'] = $order_num;//商户系统订单号,必须在商户系统内唯一；且长度不超过64字节

        $terminal_key = Cache::get('terminal_key');
        if(empty($terminal_key)){
            $terminal_key = Db::name('system_config')->where('name','terminal_key')->value('value');
        }

        $ret = $this->pre_do_execute($params, $url, $this->config['terminal_sn'],  $terminal_key);
        $result=json_decode($ret,true);
        //退款之后进行入库操作
        if($result['biz_response']['data']['status']=='SUCCESS'){
            Db::name('med_order')->where('order_num',$order_num)->update(['refund_status'=>2,'refund_amount'=>$result['biz_response']['data']['total_amount'],'refund_time'=>time()]);
        }

        return $this->returnParam(1, '撤单成功','', '');
    }


//签到接口
    function checkin($terminal_sn, $terminal_key)
    {
        $url = $this->config['api_domain'] . '/terminal/checkin';
        $params['terminal_sn'] = $terminal_sn;              //终端号
        $params['device_id'] = $this->config['device_id'];//设备唯一身份ID
        $ret = $this->pre_do_execute($params, $url, $terminal_sn, $terminal_key);
        return $ret;
    }

    function pre_do_execute($params, $url, $terminal_sn, $terminal_key)
    {
        $j_params = json_encode($params);
        $sign = $this->getSign($j_params . $terminal_key);
        $result = $this->httpPost($url, $j_params, $sign, $terminal_sn);
        return $result;
    }

    function getSign($signStr)
    {

        $md5 = Md5($signStr);
        return $md5;

    }
    function httpPost($url, $body, $sign, $sn)
    {

        $header = array(
            "Format:json",
            "Content-Type: application/json",
            "Authorization:$sn" . ' ' . $sign
        );


        $result = $this->do_execute($url, $body, $header);

        return $result;
    }

    function do_execute($url, $postfield, $header)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

        $response = curl_exec($ch);
        //var_dump(curl_error($ch));  //查看报错信息

        return $response;


        //    $httpStatusCode = curl_getinfo($ch);

        curl_close($ch);
        return $response;
    }
    protected function returnParam($status, $reason='', $data = [], $code = '') {
        $data = ['status' => $status, 'data' => $data, 'reason' => $reason, 'code' => $code];
        return json($data);
    }
    //判断中英文字符
    function strLength($str, $charset = 'utf-8') {
        if ($charset == 'utf-8')
            $str = iconv ( 'utf-8', 'gb2312', $str );
        $num = strlen ( $str );
        $cnNum = 0;
        for($i = 0; $i < $num; $i ++) {
            if (ord ( substr ( $str, $i + 1, 1 ) ) > 127) {
                $cnNum ++;
                $i ++;
            }
        }
        $enNum = $num - ($cnNum * 2);
        $number = ($enNum / 2) + $cnNum;
        return ceil ( $number );
    }
    //截取中英文字符
    public function cut_str($sourcestr, $cutlength) {
        $returnstr = '';
        $i = 0;
        $n = 0;
        $str_length = strlen ( $sourcestr ); //字符串的字节数
        while ( ($n < $cutlength) and ($i <= $str_length) ) {
            $temp_str = substr ( $sourcestr, $i, 1 );
            $ascnum = Ord ( $temp_str ); //得到字符串中第$i位字符的ascii码
            if ($ascnum >= 224) //如果ASCII位高与224，
            {
                $returnstr = $returnstr . substr ( $sourcestr, $i, 3 ); //根据UTF-8编码规范，将3个连续的字符计为单个字符
                $i = $i + 3; //实际Byte计为3
                $n ++; //字串长度计1
            } elseif ($ascnum >= 192) //如果ASCII位高与192，
            {
                $returnstr = $returnstr . substr ( $sourcestr, $i, 2 ); //根据UTF-8编码规范，将2个连续的字符计为单个字符
                $i = $i + 2; //实际Byte计为2
                $n ++; //字串长度计1
            } elseif ($ascnum >= 65 && $ascnum <= 90) //如果是大写字母，
            {
                $returnstr = $returnstr . substr ( $sourcestr, $i, 1 );
                $i = $i + 1; //实际的Byte数仍计1个
                $n ++; //但考虑整体美观，大写字母计成一个高位字符
            } else //其他情况下，包括小写字母和半角标点符号，
            {
                $returnstr = $returnstr . substr ( $sourcestr, $i, 1 );
                $i = $i + 1; //实际的Byte数计1个
                $n = $n + 0.5; //小写字母和半角标点等与半个高位字符宽...
            }
        }
        if ($str_length > $cutlength) {
            $returnstr = $returnstr . "..."; //超过长度时在尾处加上省略号
        }
        return $returnstr;
    }
}
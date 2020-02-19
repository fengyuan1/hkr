<?php
use service\DataService;
use service\NodeService;
use service\ToolsService;
use Wechat\Loader;
use think\Db;
use Qiniu\Auth;
use think\Cache;
use think\Config;
use think\Session;
use think\Hook;
use think\exception\HttpResponseException;
use think\Response;

Hook::listen('common_start');

/**
 * 打印输出数据到文件
 * @param mixed $data
 * @param bool $replace
 * @param string|null $pathname
 */

function p($data, $replace = false, $pathname = null)
{
    is_null($pathname) && $pathname = RUNTIME_PATH . date('Ymd') . '.txt';
    $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . "\n";
    $replace ? file_put_contents($pathname, $str) : file_put_contents($pathname, $str, FILE_APPEND);
}

/**
 * 获取微信操作对象
 * @param string $type
 * @return \Wechat\WechatReceive|\Wechat\WechatUser|\Wechat\WechatPay|\Wechat\WechatScript|\Wechat\WechatOauth|\Wechat\WechatMenu|\Wechat\WechatMedia
 */
function & load_wechat($type = '')
{
    static $wechat = [];
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        $config = [
            'token'          => sysconf('wechat_token'),
            'appid'          => sysconf('wechat_appid'),
            'appsecret'      => sysconf('wechat_appsecret'),
            'encodingaeskey' => sysconf('wechat_encodingaeskey'),
            'mch_id'         => sysconf('wechat_mch_id'),
            'partnerkey'     => sysconf('wechat_partnerkey'),
            'ssl_cer'        => sysconf('wechat_cert_cert'),
            'ssl_key'        => sysconf('wechat_cert_key'),
            'cachepath'      => CACHE_PATH . 'wxpay' . DS,
        ];
        $wechat[$index] = Loader::get($type, $config);
    }
    return $wechat[$index];
}

/**
 * UTF8字符串加密
 * @param string $string
 * @return string
 */
function encode($string)
{
    $chars = '';
    $len = strlen($string = iconv('utf-8', 'gbk', $string));
    for ($i = 0; $i < $len; $i++) {
        $chars .= str_pad(base_convert(ord($string[$i]), 10, 36), 2, 0, 0);
    }
    return strtoupper($chars);
}

/**
 * UTF8字符串解密
 * @param string $string
 * @return string
 */
function decode($string)
{
    $chars = '';
    foreach (str_split($string, 2) as $char) {
        $chars .= chr(intval(base_convert($char, 36, 10)));
    }
    return iconv('gbk', 'utf-8', $chars);
}

/**
 * RBAC节点权限验证
 * @param string $node
 * @return bool
 */
function auth($node)
{
    return NodeService::checkAuthNode($node);
}

/**
 * 设备或配置系统参数
 * @param string $name 参数名称
 * @param bool $value 默认是false为获取值，否则为更新
 * @return string|bool
 */
function sysconf($name, $value = false)
{
    static $config = [];
    if ($value !== false) {
        $config = [];
        $data = ['name' => $name, 'value' => $value];
        return DataService::save('SystemConfig', $data, 'name');
    }
    if (empty($config)) {
        foreach (Db::name('SystemConfig')->select() as $vo) {
            $config[$vo['name']] = $vo['value'];
        }
    }
    return isset($config[$name]) ? $config[$name] : '';
}

/**
 * array_column 函数兼容
 */
if (!function_exists("array_column")) {

    function array_column(array &$rows, $column_key, $index_key = null)
    {
        $data = [];
        foreach ($rows as $row) {
            if (empty($index_key)) {
                $data[] = $row[$column_key];
            } else {
                $data[$row[$index_key]] = $row[$column_key];
            }
        }
        return $data;
    }

}
/** 发送数据的接口  **/
function http_post_data($url, $data,$time)
{
    if($time==""){
        $time=2;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT ,3);    //允许curl执行的最长时间
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//HTTPS 调用所加参数
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    /*curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        , 'Content-Length: ' . strlen($data_string)
        )
    );*/
    ob_start();
    $output=curl_exec($ch);
    $return_content = ob_get_contents();
    ob_end_clean();
    //print_r($output);
    $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
   
    return array($return_code, $output);
}


function postdata($data, $time = '')
{
    $url = config('workerman.host') . ':' . config('workerman.port');
    $data = http_post_data($url, $data, $time);
    return $data;
}

/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr) {
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)array_to_object($v);
        }
    }
 
    return (object)$arr;
}
 
/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
 
    return $obj;
}


/**
 * 代理商分级的查询限制条件
 * @param $field
 * 查询的字段名
 * @param $merchant_id
 * 代理商ID
 * @return mixed
 */
function merchant_condition($field, $merchant_id = null)
{
    $false = '1 = 0';
    $true = '1 = 1';

    // 隐藏代理商条件查询
    return $true;

    if ($merchant_id === null) {
        if (!Session::has('user.merchant_id')) {
            return $false;
        }
        $merchant_id = Session::get('user.merchant_id');
    }

    if ($merchant_id === 1) {
        return $true;
    }

    if (Db::table('med_equ_merchant')->where('pid', $merchant_id)->find()) {
        $data = Db::table('med_equ_merchant')->select();
        $mo = array();
        foreach ($data as $k => $v) {
            if ($data[$k]['pid'] == $merchant_id) {
                $mo[$k] = $data[$k]['id'];
            }
            foreach ($mo as $items) {
                if ($items == $data[$k]['pid']) {
                    $mo[$k] = $data[$k]['id'];
                }
            }
        }
        array_push($mo, $merchant_id);
        $k = 0;
        foreach ($mo as $item) {
            $map[$field][$k] = ['=', $item];
            $k++;
        }
        $map[$field][$k] = 'or';
        return $map;

    } else {
        return [$field => ['=', $merchant_id]];
    }
}

/**
 * 代理商分级列表
 * @param $merchant_id
 * @return array|false|mixed|\PDOStatement|string|\think\Collection
 */
function get_merchant_list($merchant_id = null)
{
    if ($merchant_id === null) {
        if (!Session::has('user.merchant_id')) {
            return false;
        }
        $merchant_id = Session::get('user.merchant_id');
    }

    $list = Db::table('med_equ_merchant')
        ->where(merchant_condition('id', $merchant_id))
        ->select();
    foreach ($list as &$data) {
        $data['ids'] = join(',', ToolsService::getArrSubIds($list, $data['id']));
    }
    return ToolsService::arr2table($list);
}


function gen_url($uri, $param = [])
{
    $param = array_merge($param, ['spm' => input('spm', '')]);
    return url('@'.request()->module()) . '#' . url($uri) . '?' . http_build_query($param);
}

function ajaxReturn($code, $msg, $data = '')
{
    header('Content-type:text/json');
    die(json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE));
}

/**
 * 成功响应输出
 * @param mixed $data 输出数据
 */
function outputSuccess($data)
{
    outputJson([
        'status' => 1,
        'data' => $data,
        'reason' => ''
    ]);
}

/**
 * 错误响应输出
 * @param string $reason 错误原因
 * @param int $code 错误码
 */
function outputError($reason, $code = 0)
{
    outputJson([
        'status' => 0,
        'data' => '',
        'reason' => $reason
    ]);
}

/**
 * 响应输出json格式数据
 * @param array $data
 * @throws HttpResponseException
 */
function outputJson($data)
{
    $response = Response::create()
        ->code(200)
        ->contentType('application/json')
        ->data(json_encode($data));

    throw new HttpResponseException($response);
}

function joint_domain_pre($url){

    $request = Request()->domain();

    return $request.'/'.$url;
    // return $request.'/public/static/'.$url;
}
//网站的url
function remove_domain_pre($url)
{
    // $url = 'http://regpub.med.normal.test.benxiangai.cn/gdgsd/8d274045ed631631/04577daafd4ddd32.png';
    //正则表达式
    // $reg = '/(https|http):\/\/([^\/]+)/i';
    $reg = '/(https|http|ftp|rtsp|mms):\/\/([^\/]+)\//i';

    return preg_replace($reg, '', $url);
}
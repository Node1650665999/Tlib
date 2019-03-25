<?php
namespace Lib;
use Lib\Capture;
/**
 * Class Wx
 */
class Wx
{
    /**
     * @var string  商户号
     */
    private $mch_id = null;

    /**
     * @var string  appid
     */
    private $appid = null;

    /**
     * @var string secret
     */
    private $appsecret = null;

    /**
     * @var string  API密钥
     */
    private $key = null;

    /**
     * @var null $access_token
     */
    private $access_token = null;

    /**
     * @var array  用于保存微信返回的所有数据
     */
    private $wx_data = [];

    /**
     * @var array 配置
     */
    private  $config =
    [
        'mch_id'         => '',
        'appid'          => '',
        'appsecret'      => '',
        'key'            => '',
        'sslcert_path'   => '',
        'sslkey_path'    => '',
    ];

    /**
     * WxTool constructor.
     * @param array $param
     */
    public function __construct($param=[])
    {
        if($param)
        {
            foreach ($param as $key => $val)
            {
                $this->$key = $val;
            }
        }

        if(empty($param['appid']))
        {
            $this->mch_id       = $this->config['mch_id'];
            $this->appid        = $this->config['appid'];
            $this->appsecret    = $this->config['appsecret'];
            $this->key           = $this->config['key'];
        }
    }

    /**
     * 魔术方法
     * @param $name
     * @return mixed|null
     */
    function __get($name)
    {
        if(isset($this->$name))
        {
            return $this->$name;
        }

        if(isset($this->wx_data[$name]))
        {
            return $this->wx_data[$name];
        }

        $this->error_msg("{$name} 不存在");

        return null;
    }

    /**
     *读取某个数据
     * @param $key
     * @return mixed|null
     */
    function get($key)
    {
        return $this->$key;
    }

    /**
     * 为客户端生成调起收银台的预支付数据
         $value =
         [
            'body'          => 'your product description',
            'out_trade_no'  => 'your out_trade_no',
            'total_fee'     => 'your order amount',
            'notify_url'    => 'your notify url',
            'attach'        => json_encode('your attach')
         ];
     * @param $value
     * @param null $js_code
     * @return bool|mixed
     */
    function clientPrePayment($value, $js_code= null)
    {
        if(false == $this->unifiedOrder($value, $js_code))
        {
            return false;
        }

        return $this->clientPaySign($this->appid, $this->prepay_id);
    }

    /**
     * 下单
     * ps:下单是为了拿到 prepay_id
        $value =
        [
            'body'          => 'your product description',
            'out_trade_no'  => 'your out_trade_no',
            'total_fee'     => 'your order amount',
            'notify_url'    => 'your notify url',
            'attach'        => json_encode('your attach')
        ];
     * @param $value
     * @return bool|mixed
     */
    private function unifiedOrder($value, $js_code=null)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        if($js_code)
        {
            if(false == $this->resolve_js_code($js_code))
            {
                return false;
            }
        }

        $value['appid']              = $this->appid;
        $value['mch_id']             = $this->mch_id;
        $value['nonce_str']          = $this->random_str(20);
        $value['spbill_create_ip']   = $this->client_ip();
        $value['total_fee']          = $value['total_fee'] * 100;  // 金额单位是分
        $value['trade_type']         = empty($value['trade_type']) ? 'JSAPI' : $value['trade_type'];
        if($value['trade_type'] === 'JSAPI')
        {
              $value['openid']              = $this->openid; //trade_type=JSAPI，openid必须传
        }

        $value['sign']                = $this->makePaySign($value);

        $xml_data     = $this->array_to_xml($value);

        $res          = $this->do_post($url, $xml_data);

        if(empty($res))
        {
            return false;
        }

        $res = $this->xml_to_array($res);

        if($res['return_code'] === 'FAIL')
        {
            $this->error_msg($res['return_msg']);
            return false;
        }

        $this->merge_data($res);

        return $res;
    }

    /**
     * 企业付款
     * @param $value
     *  $value =
        [
            'openid'        => '',
            'amount'        => '',
            'desc'          => ''
        ];
     * @return bool
     */
    public function companyRefund($value)
    {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';

        $value['mch_appid']          = $this->appid;
        $value['mchid']              = $this->mch_id;
        $value['nonce_str']          = $this->random_str(20);
        $value['partner_trade_no']   = $this->random_str(20); // 自定义编号
        $value['check_name']         = 'NO_CHECK';
        $value['amount']             = $value['amount'] * 100;
        $value['spbill_create_ip']   = $this->client_ip();

        $value['sign']                = $this->makePaySign($value);

        $xml_data     = $this->array_to_xml($value);

        $res          = $this->do_post($url, $xml_data, true);

        if(empty($res))
        {
            return false;
        }

        $res = $this->xml_to_array($res);

        // 接口请求失败
        if($res['return_code'] === 'FAIL')
        {
            $this->error_msg($res['return_msg']);
            return false;
        }

        // 业务失败
        if($res['result_code'] === 'FAIL')
        {
            $this->error_msg($res['err_code'] . ':' . $res['err_code_des']);
            return false;
        }

        return $res;
    }

    /**
     * 订单退款
     * @param $value
       $value =
        [
            'out_trade_no'  => '',
            'out_refund_no' => '',
            'total_fee'     => '',
            'refund_fee'    => ''
        ];
     * @return bool|mixed
     */
    public function orderRefund($value)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

        $value['appid']              = $this->appid;
        $value['mch_id']             = $this->mch_id;
        $value['nonce_str']          = $this->random_str(20);
        $value['total_fee']          = $value['total_fee'] * 100;  // 金额单位是分
        $value['refund_fee']         = $value['refund_fee'] * 100;  // 金额单位是分

        $value['sign']                = $this->makePaySign($value);

        $xml_data     = $this->array_to_xml($value);

        $res          = $this->do_post($url, $xml_data, true);

        if(empty($res))
        {
            return false;
        }

        $res = $this->xml_to_array($res);

        // 接口请求失败
        if($res['return_code'] === 'FAIL')
        {
            $this->error_msg($res['return_msg']);
            return false;
        }

        // 业务失败
        if($res['result_code'] === 'FAIL')
        {
            $this->error_msg($res['err_code'] . ':' . $res['err_code_des']);
            return false;
        }

        $this->merge_data($res);

        return $res;
    }

    /**
     * 查询退款状态
     * @param $value
        $value =
        [
            'transaction_id'  => '',
        ];
     * @return bool|mixed
     */
    public function refundQuery($value)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';

        $value['appid']              = $this->appid;
        $value['mch_id']             = $this->mch_id;
        $value['nonce_str']          = $this->random_str(20);
        $value['sign']               = $this->makePaySign($value);

        $xml_data     = $this->array_to_xml($value);

        $res          = $this->do_post($url, $xml_data, true);

        if(empty($res))
        {
            return false;
        }

        $res = $this->xml_to_array($res);

        // 接口请求失败
        if($res['return_code'] === 'FAIL')
        {
            $this->error_msg($res['return_msg']);
            return false;
        }

        // 业务失败
        if($res['result_code'] === 'FAIL')
        {
            $this->error_msg($res['err_code'] . ':' . $res['err_code_des']);
            return false;
        }

        $this->merge_data($res);

        return $res;
    }

    /**
     * 支付通知签名
     * @param $notify
     * @return bool|mixed
     */
    public function orderNotifySign($notify)
    {
        $data     = $this->xml_to_array($notify);
        $data_bak = $data;

        if($data['return_code'] === 'FAIL')
        {
            $this->notifyReply($data['return_code'], $data['return_msg']);
        }

        $sign = $data['sign'];
        unset($data['sign']);

        if($sign !== $this->makePaySign($data))
        {
            $this->notifyReply('FAIL', '签名失败');
        }

        return $data_bak;
    }

    /**
     * 回复微信订单通知
     * @param string $code
     * @param string $msg
     */
    public function notifyReply($code = 'SUCCESS', $msg = 'OK')
    {
        echo $this->array_to_xml(['return_code' => $code, 'return_msg' => $msg]);
        exit;
    }

    /**
     * 前端调起收银台
     * @return mixed
     */
    private function clientPaySign($appid, $prepay_id)
    {
        $arr['appId']     = $appid;
        $arr['timeStamp'] = (string)time();
        $arr['nonceStr']  = $this->random_str(20);
        $arr['package']   = 'prepay_id=' . $prepay_id;
        $arr['signType']  = 'MD5';

        $arr['paySign'] = $this->MakePaySign($arr);

        unset($arr['appId']);

        return json_encode($arr);
    }

    /**
     * 签名
     *
     * @param $value
     * @return string
     */
    public function makePaySign($value)
    {
        $data = array_filter($value);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = http_build_query($data);
        //签名步骤二：在string后加入KEY
        $str = urldecode($string) . "&key=". $this->key;
        //签名步骤三：MD5加密
        $string = md5($str);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    /**
     * 获取IP
     *
     * @return mixed
     */
    private  function client_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }else {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * array 转 xml
     *
     * @param $data
     * @return bool|string
     */
    public function array_to_xml($data)
    {
        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


    /**
     * xml 转 array
     *
     * @param $xml
     * @return bool|mixed
     */
    public function xml_to_array($xml)
    {
        if (!$xml)
        {
            return false;
        }
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /**
     * 生成随机字符串
     *
     * @param $length
     * @return bool|string
     */
    function random_str($length = 32)
    {
        return substr(md5(uniqid()), -$length);
    }

    /**
     * 解析js_code 得到 session_key 和 openid
     * @param $js_code
     * @return array|bool|mixed|null|string
     */
    public function resolve_js_code($js_code)
    {
        if(! empty($this->openid))
        {
            return [];
        }

        $url = 'https://api.weixin.qq.com/sns/jscode2session';

        $param = [
                'appid'         => $this->appid,
                'secret'        => $this->appsecret,
                'js_code'       => $js_code,
                'grant_type'    => 'authorization_code'
        ];

        $url = $url . '?' . http_build_query($param);

        $data = $this->do_post($url, $param);

        if($data)
        {
            $this->merge_data($data);
        }

        return $data;
    }

    /**
     * 解析密文得到手机号
     *
     * @param $encry
     * @param $session_key
     * @param $iv
     * @return mixed
     */
    public function resolve_phone($session_key, $encry,  $iv)
    {
        if (strlen($session_key) != 24)
        {
            $this->error_msg('解析用户密文：session_key 长度必须等于24位');
            return false;
        }

        $aesKey = base64_decode($session_key);

        if (strlen($iv) != 24)
        {
            $this->error_msg('解析用户密文：iv 长度必须等于24位');
            return false;
        }

        $aesIV     = base64_decode($iv);
        $aesCipher = base64_decode($encry);
        $result    = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $data = json_decode($result, true);
        if(empty($data))
        {
            $this->error_msg('解析用户密文：密文解析结果为空');
            return false;
        }

        if ($data['watermark']['appid'] != $this->appid)
        {
            $this->error_msg('解析用户密文：appid校验失败');
            return false;
        }

        if($data)
        {
            $this->merge_data($data);
        }

        return $data;
    }


    /**
     * 生成小程序码
     * @param $param
        $param =
        [
        'scene'      => $scene,
        'page'       => $page,
        'width'      => intval($width),
        'auto_color' => $auto_color,
        'line_color' => $line_color
        ];
     * @param bool $is_echo  直接输出
     * @return bool|img_path
     */
    public function vcode($param=[], $is_echo = false)
    {
        // 只有发布的小程序才能生成小程序码
        $access_token = $this->access_token($this->appid, $this->appsecret);
        if($access_token == false)
        {
            return false;
        }
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";

        if($param)
        {
            $param['auto_color'] = false;
            $param['line_color'] = ['r' => '53', 'g' => '53', 'b' => '53'];
        }

        $img_binary = $this->do_post($url, json_encode($param));
        if($img_binary == false)
        {
            return false;
        }

        if($is_echo === true)
        {
            header('Content-Type: image/jpeg');
            echo $img_binary;
            exit;
        }

        $upload = $this->img_upload($img_binary);
        if($upload == false)
        {
            return false;
        }
        return $upload['data'];
    }

    /**
     * @param $param
     * @param bool $is_echo
     * @return bool
     */
    public function qrcode($param, $is_echo = false)
    {
        $access_token = $this->access_token($this->appid, $this->appsecret);
        if($access_token == false)
        {
            return false;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$access_token}";

        $img_binary = $this->do_post($url, json_encode($param));
        if($img_binary == false)
        {
            return false;
        }

        if($is_echo === true)
        {
            header('Content-Type: image/jpeg');
            echo $img_binary;
            exit;
        }

        $upload = $this->img_upload($img_binary);
        if($upload == false)
        {
            return false;
        }
        return $upload['data'];
    }

    /**
     * 上传图片
     * @param $img
     * @param string $url
     * @return bool|mixed
     */
    private function img_upload($img, $url='')
    {
        if(! $url)
        {
            $this->error_msg('图片上传地址不能为空');
            return false;
        }

        $pic = ['pic' => urlencode($img)];

        $upload =  $this->do_post($url, http_build_query($pic));
        if($upload['status'] == false)
        {
            $this->error_msg('小程序二维码上传失败: '. $upload['msg']);
            return false;
        }

        return $upload;
    }

    /**
     * 获取 access_token
     *
     * @param $appid
     * @param $appsecret
     * @return array|mixed
     */
    public function access_token($appid, $appsecret)
    {
        /*$url = '你的token服务器地址，该接口你需要请求微信服务器生成token(接口为cache_access_token)，然后保存';
        $param['appid']     = $appid;
        $param['appsecret'] = $appsecret;

        $url = $url . '?' . http_build_query($param);

        $res = $this->do_get($url);
        if($res == false)
        {
            return false;
        }
        return $this->access_token = $res;*/

        return null;
    }

    /**
     * 从微信服务器获取 access_token 并缓存
     *
     * @param $file
     * @param $appid
     * @param $appsecret
     * @return mixed
     */
    public function cache_access_token($appid, $appsecret, $file)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token";

        $query =
        [
            'appid'         => $appid,
            'secret'        => $appsecret,
            'grant_type'    => 'client_credential'
        ];

        $url = $url . "?" . http_build_query($query);

        $res = $this->do_get($url);

        if($res == false)
        {
            return false;
        }

        if($file)
        {
            $wx_data = $res['data'];
            $cache =
            [
                'access_token' => $wx_data['access_token'],
                'expires_in'   => time() + $wx_data['expires_in'] -100
            ];
            file_put_contents($file, json_encode($cache));
        }

        return $res;
    }

    /**
     * 调试用
     * @return array|mixed
     */
    public function refresh_access_token()
    {
        return $this->access_token($this->appid, $this->appsecret);
    }

    /**
     * 发送模板消息
     *
     * @param $param
         $param =
         [
                'touser'             => '',  //openid
                'template_id'        => '',  //template_id
                'page'               => '',
                'form_id'            => '',  // form_id|prepay_id
                'data'               => '',
          ];
     * @return array|bool|mixed|null|string
     */
    public function send_message($param)
    {
        $access_token = $this->access_token($this->appid, $this->appsecret);
        if($access_token == false)
        {
            return false;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";

        $res = $this->do_post($url, $param);

        return $res;
    }


    /**
     * post 请求
     * @param $url
     * @param $param
     * @param bool $usecert  是否使用证书
     * @return bool|mixed
     */
    private function do_post($url, $param, $usecert = false)
    {
        $curl = curl_init();
        if(stripos($url,"https://") !== false)
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);

        if ($usecert == true)
        {
            //设置证书
            curl_setopt($curl,CURLOPT_SSLCERT, $this->config['sslcert_path']);
            curl_setopt($curl,CURLOPT_SSLKEY, $this->config['sslkey_path']);
        }

        $data               = curl_exec($curl);
        $info               = curl_getinfo($curl);

        curl_close($curl);

        return $this->handle_response($data, $info);
    }

    /**
     * get 请求
     *
     * @param $url
     * @return array
     */
    private function do_get($url)
    {
        $curl = curl_init();
        if (stripos($url, "https") !== false)
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $data               = curl_exec($curl);
        $info               = curl_getinfo($curl);

        curl_close($curl);

        return $this->handle_response($data, $info);
    }

    /**
     * 处理 http response
     * @param $data
     * @param $info
     * @return bool|mixed
     */
    private function handle_response($data, $info)
    {
        if($info['http_code'] !== 200)
        {
            $msg                = "http_code={$info['http_code']}导致请求失败!";
            $this->error_msg($msg);
            return false;
        }

        if($this->is_json($data))
        {
            $data = json_decode($data, true);
        }

        if(isset($data['errmsg']))
        {
            $this->error_msg($data['errmsg']);
            return false;
        }

        return $data;
    }


    /**
     * merge 微信返回的数据
     * @param array $data
     */
    private function merge_data($data=[])
    {
        $this->wx_data = array_merge($this->wx_data, $data);
    }


    /**
     * 判断是否为json
     * @param $string
     * @return bool
     */
    private function is_json($string)
    {
        return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
    }

    /**
     *  读取/保存 错误信息
     * @param string $msg
     * @param bool $status
     * @param int $code
     * @return bool|null
     */
    public function error_msg($msg='', $status=false, $code = 0)
    {
        return Capture::error_msg($msg, $status, $code);
    }
}

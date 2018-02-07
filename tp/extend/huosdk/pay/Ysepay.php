<?php
/**
 * Ysepay.php UTF-8
 * 银盛支付
 *
 * @date    : 2017/3/29 22:09
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 */
namespace huosdk\pay;

use think\Db;
use think\Loader;
use think\Session;

class Ysepay extends Pay {
    private $config;

    /**
     * 构造函数
     *
     * @param $table_name string 数据库表名
     */
    public function __construct() {
        $_conf_file = CONF_PATH."extra/pay/ysepay/config.php";
        if (file_exists($_conf_file)) {
            $yseconf = include $_conf_file;
        } else {
            $yseconf = array();
        }
        /*
         *#noticepg_url 前台通知地址:商户系统提供，支付成功跳转商户体统，为空不跳转。银盛支付平台在此URL后追加固定的参数向商户系统跳转:Msg=“订单号|金额（单位：分），然后对Msg做Base64编码”;Check=“Msg的签名后，再进行Base64”
         *#noticebg_url 后台通知地址:商户系统提供，支付成功后，银盛支付平台返回R3501报文
        */
        $this->config = array(
            'seller_id'           => $yseconf['seller_id'],  // 商户ID
            'usercode'            => $yseconf['usercode'],  // 商户号
            'merchantname'        => $yseconf['merchantname'],  // 商户名
            'pfxpath'             => CONF_PATH.'extra/pay/ysepay/key/shanghu.pfx',  // 商户私钥证书路径(发送交易签名使用)
            'businessgatecerpath' => CONF_PATH.'extra/pay/ysepay/key/businessgate.cer',  //银盛支付公钥证书路径(接收到银盛支付回执时验签使用)
            'pfxpassword'         => $yseconf['pfxpassword'],  // 商户私钥证书密码
            'noticepg_url'        => config('domain.SDKSITE').url('Pay/Ysepay/returnurl'),
            'noticebg_url'        => config('domain.SDKSITE').url('Pay/Ysepay/notifyurl'),
            'host'                => $yseconf['host'], //银盛支付url
            'xmlpage_url'         => $yseconf['host']."/businessgate/yspay.do", //页面接口类银盛支付网关地址
            'xmlbackmsg_url'      => $yseconf['host']."/businessgate/xmlbackmsg.do", //后台接口类银盛支付网关地址
            'filemsg_url'         => $yseconf['host']."/businessgate/filemsg.do"  //文件接口类银盛支付网关地址
        );
    }

    /**
     * 移动APP支付函数
     */
    public function clientPay() {
        $this->config['sign_type'] = 'RSA';
//        $_data = array(
//            "service"        => "mobile.securitypay.pay",
//            "partner"        => trim($this->config['partner']),
//            "_input_charset" => trim(strtolower($this->config['input_charset'])),
//            "sign_type"      => strtoupper(trim($this->config['sign_type'])),
//            "notify_url"     => $this->config['notify_url'],
//            "out_trade_no"   => Session::get('order_id', 'order'),
//            "subject"        => Session::get('product_name', 'order'),
//            "body"           => Session::get('product_desc', 'order'),
//            "payment_type"   => "1",
//            "seller_id"      => trim($this->config['seller_email']),
//            "total_fee"      => Session::get('real_amount', 'order'),
//            "it_b_pay"       => "30m"
//        );
        $_my_params = array(
            "business_code"   => "mobile.securitypay.pay",
            "charset"         => 'utf-8',
            "method"          => "ysepay.online.directpay.createbyuser",
            "notify_url"      => $this->config['noticebg_url'],
            "out_trade_no"    => Session::get('order_id', 'order'),
            "partner_id"      => $this->config['usercode'],
            "return_url"      => $this->config['noticepg_url'],
            "seller_id"       => $this->config['seller_id'],
            "seller_name"     => $this->config['merchantname'],
            "sign_type"       => strtoupper(trim($this->config['sign_type'])),
            "subject"         => Session::get('product_name', 'order'),
            "timeout_express" => "1d",
            "timestamp"       => date('Y-m-d H:i:s'),
            "total_amount"    => Session::get('real_amount', 'order'),
            "version"         => "3.0"
        );
        ksort($_my_params);
        $_data = $_my_params;
        $_sign_str = "";
        foreach ($_data as $_key => $_val) {
            $_sign_str .= $_key.'='.$_val.'&';
        }
        $_sign_str = trim($_sign_str, '&');
        $_sign = $this->signEncrypt($_sign_str);
        $_my_params['sign'] = trim($_sign['check']);
        $_my_params['action'] = "https://openapi.ysepay.com/gateway.do";
        $_token = json_encode($_my_params);

        return $this->clientAjax('ysepay', $_token);
    }

    /**
     * wap端下单
     */
    public function mobilePay() {
    }

    /**
     * PC端下单
     */
    public function pcPay() {
    }

    /**
     * 钱包充值回调函数
     */
    public function walletNotify() {
    }

    /**
     * 游戏币充值回调
     */
    public function gmNotify() {
    }

    /*
     * 异步回调函数
     */
    public function notifyUrl() {
        $sign = trim($_POST['sign']);
        $result = $_POST;
        unset($result['sign']);
        ksort($result);
        $url = "";
        foreach ($result as $key => $val) {
            if ($val) {
                $url .= $key.'='.$val.'&';
            }
        }
        $data = trim($url, '&');
        /* 验证签名 */
        if ($this->signCheck($sign, $data) != true) {
            echo "验证签名失败！";
            exit;
        }
        if ($result['trade_status'] == 'TRADE_SUCCESS') {

            /* 平台交易号 */
            $out_trade_no = $result['out_trade_no'];
            /* 支付宝交易号 */
            $trade_no = $result['trade_no'];
            /* 交易金额 */
            $amount = $result['total_amount'];

            $this->selectNotify($out_trade_no, $amount, $trade_no);

            return true;
        } else {
            return false;
        }
    }

    /*
     * 返回接收页面
     */
    public function returnUrl() {
        //返回的数据处理
        $sign   = trim($_POST['sign']);
        $result = $_POST;
        unset($result['sign']);
        ksort($result);
        $url = "";
        foreach ($result as $key => $val) {
            /* 验证签名 */
            if($val) $url .= $key . '=' . $val . '&';
        }
        $data = trim($url, '&');
        /* 验证签名 */
        if($this->signCheck($sign,$data) != true){
            return false;
        }else{
            return true;
        }
    }

    /**
     *
     * 签名加密
     *
     * @param $data
     *
     * @return array
     */
    function signEncrypt($data) {
        $return = array('success' => 0, 'msg' => '', 'check' => '');
        $pkcs12 = file_get_contents($this->config['pfxpath']); //私钥
        if (openssl_pkcs12_read($pkcs12, $certs, $this->config['pfxpassword'])) {
            $privateKey = $certs['pkey'];
//            $publicKey = $certs['cert'];
            $signedMsg = "";
            if (openssl_sign($data, $signedMsg, $privateKey, OPENSSL_ALGO_SHA1)) {
                $return['success'] = 1;
                $return['check'] = base64_encode($signedMsg);
                $return['msg'] = base64_encode($data);
            }
        }

        return $return;
    }

    function signCheck($sign, $data) {
        $publickeyFile = $this->config['businessgatecerpath']; //公钥
        $certificateCAcerContent = file_get_contents($publickeyFile);
        $certificateCApemContent = '-----BEGIN CERTIFICATE-----'.PHP_EOL.chunk_split(
                base64_encode($certificateCAcerContent), 64, PHP_EOL
            ).'-----END CERTIFICATE-----'.PHP_EOL;
        // 签名验证
        $success = openssl_verify(
            $data, base64_decode($sign), openssl_get_publickey($certificateCApemContent), OPENSSL_ALGO_SHA1
        );

        return $success;
    }
}
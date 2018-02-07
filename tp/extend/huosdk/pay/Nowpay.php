<?php
/**
 * Nowpay.php UTF-8
 * 现在支付处理函数
 *
 * @date    : 2017年2月8日14:29:06
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wangchuang <wc@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2017年2月8日14:29:12
 */
namespace huosdk\pay;

use think\Db;
use think\Loader;
use think\Log;
use think\Session;

class Nowpay extends Pay {
    private $funcode;
    private $appId;
    private $mhtOrderType;
    private $mhtCurrencyType;
    private $mhtOrderTimeOut;
    private $mhtCharset;
    private $deviceType;
    private $mhtReserved;
    private $mhtSignType;
    private $notifyUrl;
    private $frontNotifyUrl;
    private $tradeUrl;
    private $secure_key;
    private $trade_paychanneltype;
    private $h5_trade_paychanneltype;
    private $h5_appId;
    private $h5_secure_key;
    private $query_funcode;
    private $trade_funcode;

    /**
     * 构造函数
     *
     * @param $table_name string 数据库表名
     */
    public function __construct() {
        // 包含配置文件
        $_conf_file = CONF_PATH."extra/pay/nowpay/config.php";
        if (file_exists($_conf_file)) {
            $_now_conf = include $_conf_file;
        } else {
            $_now_conf = array();
        }
        $this->funcode = $_now_conf['TRADE_FUNCODE'];
        $this->query_funcode = $_now_conf['QUERY_FUNCODE'];
        $this->trade_funcode = $_now_conf['TRADE_FUNCODE'];
        $this->appId = $_now_conf['appId'];
        $this->mhtOrderType = $_now_conf['TRADE_TYPE'];
        $this->mhtCurrencyType = $_now_conf['TRADE_CURRENCYTYPE'];
        $this->mhtOrderTimeOut = $_now_conf['trade_time_out'];
        $this->mhtCharset = $_now_conf['TRADE_CHARSET'];
        $this->deviceType = $_now_conf['TRADE_DEVICE_TYPE'];
        $this->mhtReserved = 'test';
        $this->mhtSignType = $_now_conf['TRADE_SIGN_TYPE'];
        $this->notifyUrl = $_now_conf['back_notify_url'];
        $this->frontNotifyUrl = $_now_conf['front_notify_url'];
        $this->tradeUrl = $_now_conf['TRADE_URL'];
        $this->secure_key = $_now_conf['secure_key'];
        $this->trade_paychanneltype = $_now_conf['TRADE_PAYCHANNELTYPE'];
        $this->h5_trade_paychanneltype = $_now_conf['H5_TRADE_PAYCHANNELTYPE'];
        $this->h5_appId = $_now_conf['H5_appId'];
        $this->h5_secure_key = $_now_conf['H5_secure_key'];
    }

    /**
     * 移动APP支付函数
     */
    public function clientPay() {
        $req = array();
        Loader::import('pay.nowpay.Services', '', '.php');
        $_notify_url = config('domain.SDKSITE').url('Pay/Nowpay/notifyurl');
        $req["funcode"] = $this->funcode;
        $req["trade_funcode"] = $this->trade_funcode;
        $req["query_funcode"] = 'MQ001';
        $req["appId"] = $this->appId;//应用ID
        $req["secure_key"] = $this->secure_key;//应用密钥
        $req["consumerId"] = 'IPN00001';
        $req["consumerName"] = 'IpaynowCS';
        $req["mhtCharset"] = $this->mhtCharset;
        $req["mhtCurrencyType"] = $this->mhtCurrencyType;
        //$req["mhtOrderAmt"]=1;
        $req["mhtOrderAmt"] = Session::get('real_amount', 'order') * 100;
        $req["mhtOrderDetail"] = "游戏充值";
        $req["mhtOrderName"] = Session::get('product_name', 'order');
        $req["mhtOrderNo"] = Session::get('order_id', 'order');
        $req["mhtOrderStartTime"] = date("YmdHis");
        $req["mhtOrderTimeOut"] = $this->mhtOrderTimeOut;
        $req["mhtOrderType"] = $this->mhtOrderType;
        $req["notifyUrl"] = $_notify_url;
        $req["payChannelType"] = $this->trade_paychanneltype;
        $req["mhtSignType"] = $this->mhtSignType;
        $req["mhtSignature"] = \Services::buildSignature($req);
        $req_str = \Services::trade($req);

        return $this->clientAjax('nowpay', $req_str);
        //header("Location:".$this->tradeUrl."?".$req_str);
    }

    /**
     * wap端下单
     */
    public function mobilePay() {
        //此处要用手机网页的配置
        Loader::import('pay.nowpay.Services', '', '.php');
        $req = array();
        $req["query_funcode"] = 'MQ002';
        $req["trade_funcode"] = $this->trade_funcode;
        $req["appId"] = $this->h5_appId;//应用ID
        $req["secure_key"] = $this->h5_secure_key;//应用密钥
        $req["mhtOrderNo"] = Session::get('order_id', 'order');
        $req["mhtOrderName"] = Session::get('product_name', 'order');
        $req["mhtOrderType"] = $this->mhtOrderType;
        $req["mhtCurrencyType"] = $this->mhtCurrencyType;
        $req["mhtOrderAmt"] = intval(Session::get('real_amount', 'order') * 100);
        $req["mhtOrderDetail"] = $req["mhtOrderName"];
        $req["mhtOrderTimeOut"] = $this->mhtOrderTimeOut;
        $req["mhtOrderStartTime"] = date("YmdHis");
        $req["notifyUrl"] = config('domain.SDKSITE').url('Pay/Nowpay/notifyh5url');
        $_order_id = Session::get('order_id', 'order');
        Session::set('now_pay_return_token', $this->getReturnToken());
        $_return_token = \huosdk\common\Simplesec::encode(session_id(), \think\Config::get('config.HSAUTHCODE'));
        $req["frontNotifyUrl"] = config('domain.SDKSITE').url('Pay/Nowpay/returnurl', ['order_id'     => $_order_id,
                                                                                           'return_token' => $_return_token]
                );
        $req["mhtCharset"] = $this->mhtCharset;
        $req["payChannelType"] = $this->h5_trade_paychanneltype;
        $req["mhtReserved"] = $this->mhtReserved;
        $req["funcode"] = $this->funcode;
        $req["deviceType"] = $this->deviceType;
        $req["mhtSignType"] = $this->mhtSignType;
        $req["mhtSignature"] = \Services::buildSignature($req);
        $req_str = \Services::trade($req);
        Log::write('---res_str1---', 'error');
        Log::write($req_str, 'error');
//        //这里要进行curl调用
        $res_str = \Net::sendMessage($req_str, $this->tradeUrl);
        Log::write('---res_str2---', 'error');
        Log::write($res_str, 'error');
        parse_str($res_str, $res_strArr);
        Log::write('---res_str3---', 'error');
        Log::write($res_strArr, 'error');
        if (!empty($res_strArr['responseCode']) && 'A001' == $res_strArr['responseCode']) {
            $_token = $res_strArr['tn'];
        } else {
            return false;
        }
        $_param = [
            'order_id'   => $_order_id,
            'timestamp'  => time(),
            'now_token'  => $_token,
            'return_url' => $req["frontNotifyUrl"],
        ];
        $_goto_weixin = config('domain.SDKSITE').url('Pay/Nowpay/gotoweixin').'?'.http_build_query($_param);

        return $this->clientAjax('h5_nowpay', $_goto_weixin);
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
    public function notifyUrl($wallet = false) {
        $request = file_get_contents('php://input');
        parse_str($request, $request_form);
        Loader::import('pay.nowpay.Services', '', '.php');
        $request_form["query_funcode"] = 'MQ001';
        $request_form["trade_funcode"] = $this->trade_funcode;
        $request_form["secure_key"] = $this->secure_key;
        if (\Services::verifySignature($request_form)) {
            $tradeStatus = $request_form['tradeStatus'];
            if ($tradeStatus != "" && $tradeStatus == "A001") {
                echo "success=Y";
                // 将订单状态写入数据表中
                // 支付成功后，修改支付表中支付状态，并将交易信息写入用户平台充值记录表ptb_charge。
                $this->selectNotify($request_form['mhtOrderNo'], $request_form['mhtOrderAmt'] / 100,$request_form['nowPayOrderNo']);
            }
        }
    }

    public function notifyH5Url($wallet = false) {
        $request = file_get_contents('php://input');
        parse_str($request, $request_form);
        Loader::import('pay.nowpay.Services', '', '.php');
        $request_form["query_funcode"] = 'MQ002';
        $request_form["trade_funcode"] = $this->trade_funcode;
        $request_form["secure_key"] = $this->h5_secure_key;
        if (\Services::verifySignature($request_form)) {
            $tradeStatus = $request_form['tradeStatus'];
            if ($tradeStatus != "" && $tradeStatus == "A001") {
                echo "success=Y";
                // 将订单状态写入数据表中
                // 支付成功后，修改支付表中支付状态，并将交易信息写入用户平台充值记录表ptb_charge。
                $this->selectNotify($request_form['mhtOrderNo'], $request_form['mhtOrderAmt'] / 100,$request_form['nowPayOrderNo']);
            }
        }
    }

    /*
     * 返回接收页面
     */
    public function returnUrl() {
    }

    /*
     * 组建订单数据
     */
    private function buildOrderdata(array $_paydata) {
        $_paydata = $_paydata;
        if (empty($_paydata['product_price']) || $_paydata['product_price'] < 0) {
            return false;
        }
        $_order_data['mem_id'] = Session::get('id', 'user');
        $_order_data['order_id'] = \huosdk\common\Commonfunc::setOrderid($_order_data['mem_id']);
        $_order_data['agent_id'] = Session::get('agent_id', 'user');
        $_order_data['app_id'] = Session::get('app_id', 'app');
        $_order_data['amount'] = $_paydata['product_price'];
        $_order_data['gm_cnt'] = 0;
        $_order_data['real_amount'] = $_order_data['amount'];
        $_order_data['rebate_cnt'] = 0;
        $_order_data['from'] = Session::get('from', 'device');
        $_order_data['status'] = 1;
        $_order_data['cpstatus'] = 1;
        $_order_data['payway'] = 0;
        $_order_data['create_time'] = time();
        $_order_data['update_time'] = $_order_data['create_time'];
        $_order_data['attach'] = isset($_paydata['ext']) ? $_paydata['ext'] : '';
        $_order_data['remark'] = '';

        return $_order_data;
    }

    /*
     * SDK预下单
     */
    public function sdkPreorder(array $_paydata = array()) {
        /* 校验入参合法性 huosdktest */
        // $_paydata = checkParam($_paydata);
        // 组建订单数据
        $_order_data = $this->buildOrderdata($_paydata);
        Session::set('order_id', $_order_data['order_id'], 'order');
        /* 1 查询余额 */
        $_wallet_remain = \huosdk\wallet\Wallet::getRemain($_order_data['mem_id'], $_order_data['app_id']);
        $_wallet_rate = \huosdk\wallet\Wallet::getRate(); /* 钱包与实际价格比例 */
        $_wallet_real_price = number_format($_wallet_remain / abs($_wallet_rate), 2, '.', ''); /* 钱包实际价值 */
        $_product_price = number_format($_order_data['amount'], 2, '.', '');
        $_no_wallet_amount = $_product_price; /* 非wallet支付金额 */
        if (0 < $_wallet_real_price && $_wallet_real_price <= $_product_price) {
            /* 实际余额少于商品价格 */
            $_order_data['gm_cnt'] = $_wallet_remain;
            $_no_wallet_amount = $_product_price - $_wallet_real_price;
        } else if ($_wallet_real_price > $_product_price) {
            /* 余额大于商品价格 */
            $_order_data['gm_cnt'] = number_format($_product_price * abs($_wallet_rate), 2, '.', '');
            $_no_wallet_amount = 0;
        }
        if ($_no_wallet_amount > 0) {
            // 除去游戏币或平台币支付 后需要支付的金额
            // 去除游戏币计算折扣
            $this->setRate($_order_data, $_no_wallet_amount);
        }
        $_pay_id = $this->insertPay($_order_data);
        if ($_pay_id) {
            return true;
        }

        return false;
    }

    /*
     * 折扣计算金额
     * huosdktest
     */
    private function setRate(&$order_data, $_no_wallet_amount) {
        $order_data['real_amount'] = $_no_wallet_amount;
        $order_data['rebate_cnt'] = 0;

        return;
    }

    /*
     * 请求数据
     */
    private function insertPayext($_pay_id) {
        $_payext_data['pay_id'] = $_pay_id;
        $_payext_data['product_id'] = Session::get('product_id', 'order');
        $_payext_data['product_name'] = Session::get('product_name', 'order');
        $_payext_data['product_desc'] = Session::get('product_desc', 'order');
        $_payext_data['deviceinfo'] = Session::get('deviceinfo', 'device');
        $_payext_data['userua'] = Session::get('userua', 'device');
        $_payext_data['agentgame'] = Session::get('agentgame', 'user');
        $_payext_data['pay_ip'] = Session::get('ip', 'device');
        $_payext_data['imei'] = Session::get('device_id', 'device');
        // $_payext_data['cityid'] = Session::get('ipaddrid', 'device');
        $_payext_data['cp_order_id'] = Session::get('cp_order_id', 'order');
        $_payext_data['product_count'] = Session::get('product_count', 'order');
        $_payext_data['exchange_rate'] = Session::get('exchange_rate', 'order');
        $_payext_data['currency_name'] = Session::get('currency_name', 'order');
        $_payext_data['server_id'] = Session::get('server_id', 'role');
        $_payext_data['server_name'] = Session::get('server_name', 'role');
        $_payext_data['role_id'] = Session::get('role_id', 'role');
        $_payext_data['role_name'] = Session::get('role_name', 'role');
        $_payext_data['party_name'] = Session::get('party_name', 'role');
        $_payext_data['role_level'] = Session::get('role_level', 'role');
        $_payext_data['role_vip'] = Session::get('role_vip', 'role');
        $_payext_data['role_balence'] = Session::get('role_balence', 'role');
        Db::name('pay_ext')->insert($_payext_data);

        return;
    }

    protected function payAction($_pay_id) {
        /* 1 插入充值扩展表 */
        $this->insertPayext($_pay_id);
        /* 2 CP 回调组装 */
        $this->setCpparam($_pay_id);
        /* 3 角色数据插入 huosdktest */
        $_r_class = new \huosdk\log\Memrolelog('mg_role_log');
        $_data['money'] = 0;
        $_data['type'] = 5;
        $_r_class->insert($_data);

        return;
    }

    protected function insertPay(array $order_data) {
        // 插入充值表
        $_pay_id = Db::name('pay')->insertGetid($order_data);
        if ($_pay_id) {
            // 异步操作其他数据
            $this->payAction($_pay_id);
            Session::set('gm_cnt', $order_data['gm_cnt'], 'order');
            Session::set('real_amount', $order_data['real_amount'], 'order');
            Session::set('rebate_cnt', $order_data['rebate_cnt'], 'order');
        }

        return $_pay_id;
    }

    protected function setCpparam($_pay_id) {
        $_param['app_id'] = Session::get('app_id', 'app');
        $_param['cp_order_id'] = Session::get('cp_order_id', 'order');
        $_param['ext'] = Session::get('ext', 'order');
        $_param['mem_id'] = Session::get('id', 'user');
        $_param['order_id'] = Session::get('order_id', 'order');
        $_param['order_status'] = 2;
        $_param['pay_time'] = time();
        $_param['product_id'] = Session::get('product_id', 'order');
        $_param['product_name'] = Session::get('product_name', 'order');
        $_param['product_price'] = Session::get('product_price', 'order');
        $_param = \huosdk\common\Commonfunc::argSort($_param);
        $_signstr = \huosdk\common\Commonfunc::createLinkstring($_param);
        /* 获取游戏信息 */
        $_g_class = new \huosdk\game\Game($_param['app_id']);
        $_g_info = $_g_class->getGameinfo($_param['app_id']);
        if (empty($_g_info['cpurl']) || empty($_g_info['app_key'])) {
            return false;
        }
        $_sign = md5($_signstr."&app_key=".$_g_info['app_key']);
        $_pc_data['pay_id'] = $_pay_id;
        $_pc_data['order_id'] = $_param['order_id'];
        $_pc_data['cp_order_id'] = Session::get('cp_order_id', 'order');
        $_pc_data['params'] = $_signstr."&sign=".$_sign;
        $_pc_data['cpurl'] = $_g_info['cpurl'];
        $_pc_data['status'] = 1;
        $_pc_data['cpstatus'] = 1;
        $_pc_data['create_time'] = $_param['pay_time'];
        $_pc_data['update_time'] = 0;
        $_pc_data['cnt'] = 0;
        $_rs = DB::name('pay_cpinfo')->insert($_pc_data);
        if ($_rs) {
            return true;
        }

        return false;
    }

    // 根据支付方式获取支付方式ID
    public function getPaywayid($_payway) {
        if (empty($_payway)) {
            return 0;
        }
        $map['payname'] = $_payway;
        $pw_id = M('payway')->where($map)->getField('id');
        if (empty($pw_id)) {
            return 0;
        } else {
            return $pw_id;
        }
    }
}
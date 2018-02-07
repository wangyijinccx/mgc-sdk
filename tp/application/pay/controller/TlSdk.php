<?php
/**
 * TlSdk.php UTF-8
 * 聚合第三方SDK回调函数
 *
 * @date    : 2017年12月18日下午4:25:29
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : zongshuo.zhang@xiguamei.com
 * @version : HUOSDK 7.0
 * @modified: 2017年12月18日下午4:25:29
 */

namespace app\pay\controller;

use app\common\controller\Base;
use think\Log;
use think\Db;

class TlSdk extends Base {
    function _initialize() {
        //parent::_initialize();
        //$this->se_class = new \huosdk\common\HuoSession();
    }

    public function testpay() {
        $cpurl = 'http://yhxy_pay_android.shouyougu.com/api_vn/callback/ios_jiujiu/callback.php';
        $param = array(
            'app_id' => 362676,
            'cp_order_id' => '82b3df5cc323442c9a98252c8f6a25',
            'ext' => urlencode('82b3df5cc323442c9a98252c8f6a25'),
            'game_role' => 10012308,
            'mem_id' => 0,
            'order_id' => urlencode('15'),
            'order_status' => 2,
            'pay_time' => strtotime('2017-12-26 11:12:01'),
            'product_id' => urlencode('1'),
            'product_name' => urlencode('元宝'),
            'product_price' => urlencode('6.00'),
        );
        $param = \huosdk\common\Commonfunc::argSort($param);
        $sign_str = \huosdk\common\Commonfunc::createLinkstring($param);
        $sign = md5($sign_str."&app_key=b377f1913bf7b2c41866b5cdbc0dc23d");
        $param["sign"] = $sign;
        \think\Log::write('zzsxxx' . print_r($param, true), 'error');
        $cp_rs = \huosdk\request\Request::cpPayback($cpurl, $param);
        \think\Log::write('zzsxxx cp rs ' . $cp_rs, 'error');
    }

    // 太浪的支付回调
    public function notify() {
        \think\Log::write('zzsxxx come in notify 0...............................', 'error');
        $param = array();
        // 太浪的type为2
        $sdk_type = 2;
        // 获取太浪传来的数据
        $param["app_id"] = $this->request->param('appId');
        $param["cp_order_id"] = $this->request->param('remark');
        $game_role = $this->request->param('gameRole');
        // 支付成功，只有支付成功的时候太浪才调用
        $param["order_status"] = 2;
        $param["pay_time"] = $this->request->param('payTime');
        //$_param['pay_time'] = time();
        $param["product_id"] = 1;
        $param["product_name"] = $this->request->param('productName');
        $param["product_price"] = urlencode($this->request->param('amount'));
        $ext = $this->request->param('remark');
        $param["mem_id"] = $this->request->param('memId');
        $order_id = $this->request->param('orderId');
        $game_area = $this->request->param('gameArea');
        $pay_way = $this->request->param('payWay');
        $product_desc = $this->request->param('productDesc');
        //TODO: 太浪的验签
        $sdk_param = Db::name('sdk_param')
                       ->where(
                           array(
                               'third_app_id'   => $param["app_id"],
                               'third_sdk_type' => $sdk_type,
                           )
                       )->find();
        if (empty($sdk_param)) {
            return hs_api_responce(201, '通知失败，没有配置参数', array());
        }
        \think\Log::write('zzsxxx come in notify 1...............................', 'error');
        $code = $sdk_param["third_code"];
        $tl_sign_str = 'amount='.$param["product_price"].'&appId='.$param["app_id"].'&gameArea='.$game_area
                       .'&gameRole='.$game_role.'&memId='.$param["mem_id"].'&orderId='.$order_id
                       .'&payTime='.$param["pay_time"].'&payWay='.$pay_way.'&productDesc='.$product_desc
                       .'&productName='.$param["product_name"].'&remark='.$param["cp_order_id"].$code;
        \think\Log::write('/*++++++++++++md5++++++++++*$tl_sign_str/', 'error');
        \think\Log::write($tl_sign_str, 'error');
        $tl_sign = md5($tl_sign_str);
        if ($tl_sign != $this->request->param('sign')) {
            return hs_api_responce(201, '通知失败，太浪验签失败', array());
        }
        \think\Log::write('zzsxxx come in notify 2...............................', 'error');
        // 插入c_sdk_order对账数据
        $status = 0;
        $sdk_order = Db::name('sdk_order')->where('cp_order_id', $param["cp_order_id"])->find();
        $param["pay_time"] = strtotime($param["pay_time"]);
        $rtl_order_id = 'tl_' . \huosdk\common\Commonfunc::setOrderid($param["mem_id"]);
        \think\Log::write('zzsxxx come in notify 20...............................', 'error');
        \think\Log::write('/*+++++++++++++$param1+++++++++*/', 'error');
        \think\Log::write($param, 'error');
        if (empty($sdk_order) || empty($sdk_order["order_id"])) {
            $data = array(
                "sdk_type"       => $sdk_type,
                "cp_order_id"    => $param["cp_order_id"],
                "pay_time"       => $param["pay_time"],
                "product_name"   => $param["product_name"],
                "product_price"  => $param["product_price"],
                "status"         => 0,
                "third_order_id" => $order_id,
                "third_app_id"   => $param["app_id"],
                "order_id"       => $rtl_order_id,
                "mem_id"         => $param["mem_id"]
            );
            Db::name("sdk_order")->insert($data);
        } else {
            $status = $sdk_order["status"];
            $rtl_order_id = $sdk_order["order_id"];
        }
        if (empty($rtl_order_id)) {
            return hs_api_responce(201, '通知失败，西瓜妹订单号生成错误', array());
        }
        \think\Log::write('zzsxxx come in notify 3...............................', 'error');
        // 平台的订单号，跟第三方对账使用
        $param["order_id"] = $rtl_order_id;
        $param["app_id"] = $sdk_param["game_id"];
        $param = \huosdk\common\Commonfunc::argSort($param);
        $sign_str = \huosdk\common\Commonfunc::createLinkstring($param);
        \think\Log::write('/*+++++++++++++$sign_str+++++++++*/', 'error');
        \think\Log::write($sign_str, 'error');
        /* 获取游戏信息 */
        $g_class = new \huosdk\game\Game($sdk_param['game_id']);
        $g_info = $g_class->getGameinfo($sdk_param['game_id']);
        if (empty($g_info['cpurl']) || empty($g_info['app_key'])) {
            return hs_api_responce(201, '通知失败，没有配置CP回调地址', array("order_id" => $rtl_order_id));
        }
        $cpurl = $g_info["cpurl"];
        $sign_str = $sign_str."&app_key=".$g_info['app_key'];
        $sign = md5($sign_str);
        $param["sign"] = $sign;
        \think\Log::write('zzsxxx come in notify 4...............................', 'error');
        if (1 != $status) {
            $i = 0;
            while (1) {
                // 异步回调CP信息
                \think\Log::write('zzsxxx huidiao url is ' . $cpurl, 'error');
                $cp_param = $sign_str . "&sign=" . $sign . '&ext=' . $ext;
                \think\Log::write('zzsxxx huidiao param is ' . $cp_param, 'error');
                $cp_rs = \huosdk\request\Request::cpPayback($cpurl, $cp_param);
                \think\Log::write('zzsxxx huidiao param is '.$cp_param, 'error');
                if ($cp_rs > 0) {
                    $status = 1;
                    break;
                } else {
                    $status = 2;
                    $i++;
                    sleep(1);
                }
                if ($i == 3) {
                    break;
                }
            }
        }
        \think\Log::write('zzsxxx come in notify 5...............................', 'error');
        Db::name('sdk_order')->where(
            array(
                'cp_order_id' => $param['cp_order_id']
            )
        )->setField('status', $status);

        if (1 == $status) {
            return hs_api_responce(200, '通知成功', array("order_id" => $rtl_order_id));
        }
        return hs_api_responce(201, '通知CP失败', array());
    }
}

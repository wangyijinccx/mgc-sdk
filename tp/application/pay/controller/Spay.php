<?php
/**
 * Spay.php UTF-8
 * 微付通对外函数
 *
 * @date    : 2016年11月18日下午4:25:52
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2016年11月18日下午4:25:52
 */
namespace app\pay\controller;

use app\common\controller\Base;
use think\Log;

class Spay extends Base {
    protected $se_class;
    function _initialize() {
        parent::_initialize();
        $this->se_class = new \huosdk\common\HuoSession();
    }

    public function notifyurl() {
        $_class = new \huosdk\pay\Spay();
        $_class->notifyUrl();
    }

    public function returnurl() {
        $_class = new \huosdk\pay\Spay();
        $_info = $_class->returnUrl();
        $_msg = "亲，恭喜您支付成功，请点击关闭按钮关闭！";
        $_de_info = json_decode($_info, true);
        $_token = $this->request->param('return_token');
        if($_token) {
          $this->se_class->initSession($_token);
        }
        if ("1" == $_de_info['status']) {
            $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
        }
        if (3 == \think\Session::get('pay_from', 'order')) {
            $_return_token = '';
        } else {
            $_return_token = urldecode(\think\Session::get('spay_return_token'));
        }
        if (1 == \think\Session::get('pay_sdk', 'order')) {
            /* 旧接口进来的 */
            $_return_token = '';
        }
        $this->assign('info', $_de_info);
        $this->assign('msg', $_msg);
        $this->assign('return_token', $_return_token);

        return $this->fetch();
    }

    /**
     * 校验订单是否OK
     *
     * @return mixed
     */
    public function checkurl() {
        $_order_id = $this->request->param('order_id');
        $_timestamp = $this->request->param('timestamp');
        if (empty($_order_id) || empty($_timestamp)) {
            return $this->fetch('spay/showurl');
        }
        /* 响应时间超过5s 超时 */
        if ($_timestamp < time() - 5) {
            return $this->fetch('spay/showurl');
        }
        $_status = \think\Db::name('pay')->where('order_id', $_order_id)->value('status');
        if (empty($_status) || PAYSTATUS_SUCCESS != $_status) {
            echo PAYSTATUS_NOPAY;
            exit;
        }
        echo PAYSTATUS_SUCCESS;
        exit;
    }

    public function gotoweixin() {
        $_order_id = $this->request->param('order_id');
        $_now_token = $this->request->param('now_token');
        $_return_url = $this->request->param('return_url');
        $this->assign('token', $_now_token);
        $this->assign('return_url', $_return_url);
        $this->assign(
            'query_url',
            SDKSITE.url('Pay/Spay/checkurl', array('order_id' => $_order_id))
        );

        return $this->fetch();
    }
}
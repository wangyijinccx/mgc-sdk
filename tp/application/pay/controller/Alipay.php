<?php
/**
 * Alipay.php UTF-8
 * 支付宝对外函数
 *
 * @date    : 2016年11月18日下午4:25:29
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2016年11月18日下午4:25:29
 */
namespace app\pay\controller;

use app\common\controller\Base;
use think\Log;

class Alipay extends Base {
    protected $se_class;
    function _initialize() {
        parent::_initialize();
        $this->se_class = new \huosdk\common\HuoSession();
    }

    public function notifyurl() {
        $_ali_class = new \huosdk\pay\Alipay();
        $_ali_class->notifyUrl();
    }

    public function returnurl() {
        $_ali_class = new \huosdk\pay\Alipay();
        $_info = $_ali_class->returnurl();
        $_msg = "亲，恭喜您支付成功，请点击关闭按钮关闭！";
        if ("3" == $_info['status']) {
            $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
        }
        $_pay_token = $this->request->param('return_token');
        $_rs = $this->se_class->initSession($_pay_token);
        if (false === $_rs) {
            $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
            $this->assign('msg', $_msg);

            return $this->fetch();
        }
        if (3 == \think\Session::get('pay_from', 'order')) {
            $_return_token = '';
        } else {
            $_return_token = urldecode(\think\Session::get('alipay_return_token'));
        }
        if (1 == \think\Session::get('pay_sdk', 'order')) {
            /* 旧接口进来的 */
            $_return_token = '';
        }
        $this->assign('info', $_info);
        $this->assign('msg', $_msg);
        $this->assign('return_token', $_return_token);
        return $this->fetch();
    }

    /**
     * @return mixed
     */
    public function submit() {
        $_token = $this->request->param('token');
        $_order_id = $this->request->param('order_id');
        $_pay_token = $this->request->param('pay_token');
        if (empty($_token) || empty($_order_id) || empty($_pay_token)) {
            $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
            $this->assign('msg', $_msg);

            return $this->fetch();
        }
        $_rs = $this->se_class->initSession($_token);
        if (false === $_rs) {
            $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
            $this->assign('msg', $_msg);

            return $this->fetch();
        }
        $_check_pay_token = \think\Session::get('alipay_token');
        if ($_check_pay_token != $_pay_token) {
            $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
            $this->assign('msg', $_msg);

            return $this->fetch();
        }
        $_html = \think\Session::get('pay_html');
        $_html = $_html."<script>document.forms['alipaysubmit'].submit();</script>";
        echo $_html;
        exit;
    }

    /**
     * 支付宝
     *
     * @return mixed
     */
    public function showurl() {
        $_pay_token = $this->request->param('token');
        $_rs = $this->se_class->initSession($_pay_token);
        if (false === $_rs) {
            $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
            $this->assign('msg', $_msg);

            return $this->fetch();
        }
        if (3 == \think\Session::get('pay_from', 'order')) {
            $_return_token = '';
        } else {
            $_return_token = urldecode(\think\Session::get('alipay_return_token'));
        }
        if (1 == \think\Session::get('pay_sdk', 'order')) {
            /* 旧接口进来的 */
            $_return_token = '';
        }
        $this->assign('return_token', $_return_token);

        return $this->fetch();
    }
}
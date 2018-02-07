<?php
/**
 * Nowpay.php UTF-8
 * 现在支付对外函数
 *
 * @date    : 2017年2月8日14:40:17
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wangchuang <wc@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2017年2月8日14:40:41
 */

namespace app\pay\controller;

use app\common\controller\Base;

class Nowpay extends Base {
    protected $se_class;
    function _initialize() {
        parent::_initialize();
        $this->se_class = new \huosdk\common\HuoSession();
    }

    public function notifyurl() {
        $_class = new \huosdk\pay\Nowpay();
        $_class->notifyUrl();
    }

    public function notifyh5url() {
        $_class = new \huosdk\pay\Nowpay();
        $_class->notifyH5Url();
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
            return $this->fetch('nowpay/showurl');
        }
        /* 响应时间超过5s 超时 */
        if ($_timestamp < time() - 5) {
            return $this->fetch('nowpay/showurl');
        }
        $_status = \think\Db::name('pay')->where('order_id', $_order_id)->value('status');
        if (empty($_status) || PAYSTATUS_SUCCESS != $_status) {
            echo PAYSTATUS_NOPAY;
            exit;
        }
        echo PAYSTATUS_SUCCESS;
        exit;
    }

    /**
     * 前端通知地址
     *
     * @return mixed
     */
    public function returnurl() {
        $_order_id = $this->request->param('order_id');
        $_info['order_id'] = $_order_id;
        $_msg = "亲，请点击关闭按钮关闭！";
        $_status = \think\Db::name('pay')->where('order_id', $_order_id)->value('status');
        if (empty($_status) || PAYSTATUS_SUCCESS != $_status) {
            $_info['order_id'] = 'aa';
        }
        $_token = $this->request->param('return_token');
        if($_token) {
            $_rs = $this->se_class->initSession($_token);
            if (false === $_rs) {
                $_msg = "亲，您支付失败了，请点击关闭按钮重试！";
                $this->assign('msg', $_msg);

                return $this->fetch();
            }
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
        $this->assign('return_token', $_return_token);
        $this->assign('info', $_info);
        $this->assign('msg', $_msg);

        return $this->fetch();
    }

    public function gotoweixin() {
        $_order_id = $this->request->param('order_id');
        $_now_token = $this->request->param('now_token');
        $_return_url = $this->request->param('return_url');
        $_timestamp = $this->request->param('timestamp');
        /* 响应时间超过5s 超时 */
//        if ($_timestamp < time() - 5) {
//            return $this->fetch('nowpay/showurl');
//        }
        $this->assign('token', $_now_token);
        $this->assign('return_url', $_return_url);
        $this->assign(
            'query_url',
            \think\Config::get('domain.SDKSITE').url('Pay/Nowpay/checkurl', array('order_id' => $_order_id))
        );

        return $this->fetch();
    }
}
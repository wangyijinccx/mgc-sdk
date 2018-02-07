<?php
/**
 * Oapay.php UTF-8
 * sdk支付成功后调用
 *
 * @date    : 2017/5/25 18:12
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : guxiannong <gxn@huosdk.com>
 * @version : HUOOA 1.0
 */
namespace huosdk\oa;
class Oapay extends Oa {
    public function __construct() {
        parent::__construct();
    }

    /**
     *
     * 自定义错误处理
     *
     * @param        $msg   输出的信息
     * @param string $level 输出等级
     */
    private function _error($msg, $level = 'error') {
        $_info = 'huosdk\oa\Oapay Error:'.$msg;
        \think\Log::record($_info, $level);
    }

    /**
     * http://doc.1tsdk.com/69?page_id=1146
     * 玩家充值接口
     *
     * @param array $param
     *
     * @return bool
     */
    public function pay(array $param) {
        $func = 'MEM_PAY_URL';
        if (empty($param['create_time'])) {
            $this->_error('create_time 为空', 'error');

            return false;
        } else {
            $_query_param['time'] = $param['create_time'];
        }
        if (empty($param['app_id'])) {
            $this->_error('app_id 为空', 'error');

            return false;
        } else {
            $_query_param['app_id'] = $param['app_id'];
        }
        if (empty($param['username'])) {
            if (isset($param['mem_id']) && $param['mem_id']) {
                $_username = $this->getUersnameById($param['mem_id']);
            }
            if (isset($_username) && $_username) {
                $_query_param['username'] = $_username;
            } else {
                $this->_error('username 为空', 'error');

                return false;
            }
        } else {
            $_query_param['username'] = $param['username'];
        }
        if (empty($param['agent_id'])) {
            $_query_param['agentname'] = '';
        } else {
            $_query_param['agentname'] = $this->getAgentnamebById($param['agent_id']);
        }
        if (empty($param['pay_ip'])) {
            $this->_error('ip 为空', 'error');

            return false;
        } else {
            $_query_param['ip'] = $param['pay_ip'];
        }
        if (empty($param['imei'])) {
            $_query_param['device_id'] = '';
        } else {
            $_query_param['device_id'] = $param['imei'];
        }
        if (empty($param['from'])) {
            $this->_error('from 为空', 'error');

            return false;
        } else {
            $_query_param['from'] = $param['from'];
        }
        if (empty($param['from'])) {
            $this->_error('from 为空', 'error');

            return false;
        } else {
            $_query_param['from'] = $param['from'];
        }
        if (empty($param['order_id'])) {
            $this->_error('order_id 为空', 'error');

            return false;
        } else {
            $_query_param['order_id'] = $param['order_id'];
        }
        if (empty($param['payway'])) {
            $this->_error('payway 为空', 'error');

            return false;
        } else {
            $_query_param['payway'] = $param['payway'];
        }
        if (empty($param['amount'])) {
            $this->_error('amount 为空', 'error');

            return false;
        } else {
            $_query_param['amount'] = $param['amount'];
        }
        if (empty($param['status'])) {
            $this->_error('status 为空', 'error');

            return false;
        } else {
            $_query_param['status'] = $param['status'];
        }
        if (empty($param['real_amount'])) {
            $_query_param['real_amount'] = 0;
        } else {
            $_query_param['real_amount'] = $param['real_amount'];
        }
        if (empty($param['rebate_cnt'])) {
            $_query_param['rebate_cnt'] = 0;
        } else {
            $_query_param['rebate_cnt'] = $param['rebate_cnt'];
        }
        if (empty($param['gm_cnt'])) {
            $_query_param['gm_cnt'] = 0;
        } else {
            $_query_param['gm_cnt'] = $param['gm_cnt'];
        }
        if (empty($param['from'])) {
            $this->_error('from 为空', 'error');

            return false;
        } else {
            $_query_param['from'] = $param['from'];
        }
        if (empty($param['role_level'])) {
            $_query_param['role_level'] = '';
        } else {
            $_query_param['role_level'] = $param['role_level'];
        }
        if (empty($param['role_name'])) {
            $_query_param['role_name'] = '';
        } else {
            $_query_param['role_name'] = $param['role_name'];
        }
        if (empty($param['role_id'])) {
            $_query_param['role_id'] = '';
        } else {
            $_query_param['role_id'] = $param['role_id'];
        }
        if (empty($param['server_id'])) {
            $_query_param['server_id'] = '';
        } else {
            $_query_param['server_id'] = $param['server_id'];
        }
        if (empty($param['server_name'])) {
            $_query_param['server_name'] = '';
        } else {
            $_query_param['server_name'] = $param['server_name'];
        }
        if (empty($param['userua'])) {
            $_query_param['userua'] = '';
        } else {
            $_query_param['userua'] = $param['userua'];
        }

        return $this->request($func, $_query_param);
    }
}
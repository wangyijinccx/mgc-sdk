<?php
namespace huosdk\oa;
class Oalogin extends Oa {
    public function __construct() {
        parent::__construct();
    }

    /**
     * http://doc.1tsdk.com/69?page_id=1136
     * 玩家登录接口
     *
     * @param array $param
     *
     * @return bool
     */
    public function login(array $param) {
        if (1 == $param['flag']) {
            $func = 'MEM_REG_URL';
            if (empty($param['reg_time'])) {
                $this->_error('reg_time 为空', 'error');

                return false;
            } else {
                $_query_param['time'] = $param['reg_time'];
            }
        } else {
            $func = 'MEM_LOGIN_URL';
            if (empty($param['login_time'])) {
                $this->_error('login_time 为空', 'error');

                return false;
            } else {
                $_query_param['time'] = $param['login_time'];
            }
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
        if (empty($param['login_ip'])) {
            $this->_error('ip 为空', 'error');

            return false;
        } else {
            $_query_param['ip'] = $param['login_ip'];
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
        if (empty($param['userua'])) {
            $_query_param['userua'] = '';
        } else {
            $_query_param['userua'] = $param['userua'];
        }

        return $this->request($func, $_query_param);
    }

    /**
     *
     * 自定义错误处理
     *
     * @param        $msg   输出的信息
     * @param string $level 输出等级
     */
    private function _error($msg, $level = 'error') {
        $_info = 'huosdk\oa\Oalogin Error:'.$msg;
        \think\Log::record($_info, $level);
    }
}
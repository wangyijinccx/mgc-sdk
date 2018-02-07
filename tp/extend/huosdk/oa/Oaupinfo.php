<?php
namespace huosdk\oa;
class Oaupinfo extends Oa {
    public function __construct() {
        parent::__construct();
    }

    /**
     * http://doc.1tsdk.com/69?page_id=1143
     * 玩家上传角色信息接口
     *
     * @param array $param
     *
     * @return bool
     */
    public function upInfo(array $param) {
        $func = 'MEM_UPINFO_URL';
        if (empty($param['create_time'])) {
            $this->_error('time 为空', 'error');

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
        if (empty($param['ip'])) {
            $this->_error('ip 为空', 'error');

            return false;
        } else {
            $_query_param['ip'] = $param['ip'];
        }
        if (empty($param['device_id'])) {
            $_query_param['device_id'] = '';
        } else {
            $_query_param['device_id'] = $param['device_id'];
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
        if (empty($param['role_level'])) {
            $_query_param['role_level'] = '';
        } else {
            $_query_param['role_level'] = $param['role_level'];
        }
        if (empty($param['role_id'])) {
            $_query_param['role_id'] = '';
        } else {
            $_query_param['role_id'] = $param['role_id'];
        }
        if (empty($param['role_name'])) {
            $_query_param['role_name'] = '';
        } else {
            $_query_param['role_name'] = $param['role_name'];
        }
        if (empty($param['server_id'])) {
            $this->_error('server_id 为空', 'error');

            return false;
        } else {
            $_query_param['server_id'] = $param['server_id'];
        }
        if (empty($param['server_name'])) {
            $this->_error('server_name 为空', 'error');

            return false;
        } else {
            $_query_param['server_name'] = $param['server_name'];
        }

        return $this->request($func, $_query_param);
    }

    /*
     * 获取直播信息
     */
    public function getWebinarInfo(array $param) {
        $func = 'GET_WEBINAR_URL';
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
        if (empty($param['server_id'])) {
            $this->_error('server_id 为空', 'error');

            return false;
        } else {
            $_query_param['server_id'] = $param['server_id'];
        }
        if (empty($param['app_id'])) {
            $this->_error('app_id 为空', 'error');

            return false;
        } else {
            $_query_param['app_id'] = $param['app_id'];
        }

        return $this->requestForWebinar($func, $_query_param);
    }

    /**
     *
     * 自定义错误处理
     *
     * @param        $msg   输出的信息
     * @param string $level 输出等级
     */
    private function _error($msg, $level = 'error') {
        $_info = 'huosdk\oa\Oaupinfo Error:'.$msg;
        \think\Log::record($_info, $level);
    }
}
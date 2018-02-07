<?php
/**
 * Oacallback.php UTF-8
 *
 *
 * @date    : 2017/6/16 10:08
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : guxiannong <gxn@huosdk.com>
 * @version : HUOOA 1.0
 */

namespace app\oa\controller;
class OaFirstFoster extends Oacallback {
    function _initialize() {
        parent::_initialize();
        $this->firstFosterCheck();
        $this->index();
    }

    public function firstFosterCheck() {
        $this->checkTypeId();
        $this->checkGameId();
        $this->checkUsername();
        $this->checkSerCode();
        $this->checkRoleName();
        $this->checkMemberRole();
    }

    public function checkTypeId() {
        if (!isset($this->param['type_id']) || empty($this->param['type_id'])) {
            return hs_api_responce('407', '类型ID错误');
        }

        return true;
    }

    public function checkGameId() {
        if (empty($this->param['game_id'])) {
            return hs_api_responce('405', '游戏ID错误');
        }

        return true;
    }

    public function checkUsername() {
        if (empty($this->param['username'])) {
            return hs_api_responce('406', '玩家用户名错误');
        }

        return true;
    }

    public function checkSerCode() {
        if (empty($this->param['ser_code'])) {
            return hs_api_responce('415', '区服code错误');
        }

        return true;
    }

    public function checkRoleName() {
        if (empty($this->param['role_name'])) {
            return hs_api_responce('415', '角色名错误');
        }

        return true;
    }

    public function checkMemberRole() {
        if (empty($this->param['role_name'])) {
            return hs_api_responce('414', '角色名错误'.$this->param['role_name']);
        }
        /* 找到玩家 和 玩家角色 */
        $_mem_map['username'] = $this->param['username'];
        $_member_info = \think\Db::name('members')->where($_mem_map)->find();
        if (empty($_member_info)) {
            return hs_api_responce('501', '玩家信息错误');
        }
        $_mem_id = $_member_info['id'];/* 玩家id */
        $this->param['oa_mem_id'] = $this->param['mem_id'];
        $this->param['sdk_mem_id'] = $_mem_id;
        $this->param['sdk_agent_id'] = $_member_info['agent_id'];
        $this->param['agentgame'] = '';
        if (!isset($this->param['sdk_agent_name'])
            || (isset($this->param['sdk_agent_name'])
                && !$this->param['sdk_agent_name'])
        ) {
            if (isset($_member_info['agent_id']) && $_member_info['agent_id']) {
                $_us_map = [];
                $_us_map['agent_id'] = $_member_info['agent_id'];
                $_us_map['app_id'] = $this->param['game_id'];
                $this->param['agentgame']
                    = $_member_info = \think\Db::name('agent_game')->where($_us_map)->value('agentgame');
            }
        } else {
            if (!$_member_info['agent_id']) {
                $_us_map = [];
                $_us_map['user_login'] = $this->param['sdk_agent_name'];
                $_agent_info = \think\Db::name('users')->where($_us_map)->find();
                $this->param['agent_id'] = $_agent_info['id'];
                $_us_map = [];
                $_us_map['agent_id'] = $_agent_info['id'];
                $_us_map['app_id'] = $this->param['game_id'];
                $this->param['agentgame']
                    = $_member_info = \think\Db::name('agent_game')->where($_us_map)->value('agentgame');
            }
        }
        $_map['mem_id'] = $_mem_id;
        $_map['app_id'] = $this->param['game_id'];
        $_map['server_id'] = $this->param['ser_code'];
        $_map['role_name'] = $this->param['role_name'];
        $_member_role_info = \think\Db::name('mg_role_log')->where($_map)->find();
        if (empty($_member_role_info)) {
            $_last_sql = \think\Db::name('mg_role_log')->getLastSql();

            return hs_api_responce('414', '角色名错误'.$_last_sql.'bbb');
        }

        return true;
    }

    /**
     *
     */
    public function index() {
        $this->firstFosterCheck();
        switch ($this->param['type_id']) {
            case 1:
                $this->gmFirst();
                break;
            case 2:
                $this->gmFoster();
                break;
        }
    }

    public function gmFirst() {/* 申请首充 */
        $this->checkIsFirst();
        /* 直接插入扶植记录表 */
        $_re = $this->addGmLog();
        /* 首充完直接发币？发完币绑定对应推广员 */
        if ($_re && 1 == 2) {
            $this->giveMoney();
            $sdk_gm_log_id = $_re;
            $this->updateGmLog($sdk_gm_log_id, 2, '首充成功');
            $this->updateMembers();/* 更新状态 */
            $this->param['reason'] = '首充直接通过';
            $this->oaNotify();/* 异步通知oa */
        }

        return hs_api_responce('200', '申请提交成功');
    }

    public function gmFoster() {/* 申请扶植 */
        /* 直接插入扶植记录表 */
        $_re = $this->addGmLog();
        if (empty($_re)) {
            return hs_api_responce('502', '信息错误');
        }

        return hs_api_responce('200', '申请提交成功');
    }

    public function checkIsFirst() {
        $this->checkAgent();
        $this->checkPayFirst();
    }

    public function addGmLog() {
        $_insert_arr = [];
        $_insert_arr['oa_gm_id'] = $this->param['id'];
        $_insert_arr['node_id'] = $this->param['node_id'];
        $_insert_arr['node_name'] = $this->param['node_name'];
        $_insert_arr['agent_id'] = isset($this->param['sdk_agent_id']) ? $this->param['sdk_agent_id'] : 0;
        $_insert_arr['username'] = $this->param['username'];
        $_insert_arr['oa_mem_id'] = $this->param['oa_mem_id'];
        $_insert_arr['mem_id'] = $this->param['sdk_mem_id'];
        $_insert_arr['plat_id'] = $this->param['plat_id'];
        $_insert_arr['type_id'] = $this->param['type_id'];
        $_insert_arr['oa_app_id'] = $this->param['oa_app_id'];
        $_insert_arr['oa_server_id'] = $this->param['server_id'];
        $_insert_arr['ser_code'] = $this->param['ser_code'];
        $_insert_arr['role_name'] = $this->param['role_name'];
        $_insert_arr['game_id'] = $this->param['game_id'];
        $_insert_arr['money'] = $this->param['money'];
        $_insert_arr['check_status'] = $this->param['check_status'];
        $_insert_arr['status'] = $this->param['status'];
        $_insert_arr['content'] = $this->param['content'];
        $_insert_arr['check_reason'] = $this->param['check_reason'];
        $_insert_arr['fail_reason'] = $this->param['fail_reason'];
        $_time = time();
        $_insert_arr['create_time'] = $_time;
        $_insert_arr['update_time'] = $_time;
        $_re = \think\Db::name('gm_log')->insertGetId($_insert_arr);
        if (empty($_re)) {
            return hs_api_responce('502', '信息错误');
        }

        return $_re;
    }

    public function giveMoney() {
        $give_user_id = 1;/* 默认的首充发币帐号 */
        $this->Inc($this->param['sdk_mem_id'], $this->param['game_id'], $this->param['money']);
        $this->addIncRecord(
            $give_user_id, $this->param['sdk_mem_id'], $this->param['game_id'], $this->param['money'],
            $this->param['content']
        );
    }

    public function updateGmLog($id = 0, $status = 0, $reason = '') {
        if (empty($id) || empty($status) || empty($reason)) {
            return false;
        }
        $_map['id'] = $id;
        $_up_data['status'] = $status;
        $this->param['result_status'] = $status;
        if (3 == $status) {
            /* 失败时 */
            $_up_data['fail_reason'] = (isset($this->param['fail_reason'])) ? $reason.($this->param['fail_reason'])
                : $reason;
            $this->param['result_reason'] = $_up_data['fail_reason'];
        } else {
            $_up_data['check_reason'] = isset($this->param['check_reason']) ? $reason.($this->param['check_reason'])
                : $reason;
            $this->param['result_reason'] = $_up_data['check_reason'];
        }
        $_up_data['update_time'] = time();
        $_info = \think\Db::name('gm_log')->where($_map)->update($_up_data);
        if (false === $_info) {
            return hs_api_responce('601', '更新状态失败');
        } else {
            return true;
        }
    }

    public function updateMembers() {
        $_mem_map['id'] = $this->param['sdk_mem_id'];
        $_up_data = [];
        $_up_data['agentgame'] = $this->param['agentgame'];
        $_up_data['agent_id'] = $this->param['sdk_agent_id'];
        $_up_data['update_time'] = time();
        $_u_re = \think\Db::name('members')->where($_mem_map)->update($_up_data);
        if (empty($_u_re)) {
            return hs_api_responce('402', '更新用户归属错误');
        }

        return true;
    }

    public function oaNotify() {
        $data = [];
        $data['id'] = $this->param['id'];
        $data['result_status'] = $this->param['result_status'];
        $data['result_reason'] = $this->param['result_reason'];
        $data['app_id'] = $this->param['game_id'];
        $data['type_id'] = $this->param['type_id'];
        $data['status'] = $this->param['result_status'];
        $data['reason'] = $this->param['reason'];
        \think\Log::write($data, 'debug');
        $info = $this->oa_class->request('GM_FIRST_URL', $data);
        \think\Log::write($info, 'debug');

        return true;
    }

    public function checkAgent() {
        $_mem_map = [];
        $_mem_map['username'] = $this->param['username']; //;
        $_member_info = \think\Db::name('members')->where($_mem_map)->find();
        if (empty($_member_info) || (!array_key_exists('agentgame', $_member_info))
            || (!array_key_exists(
                'agent_id', $_member_info
            ))
        ) {
            \think\Log::write($_member_info, 'error');
            \think\Log::write($_mem_map, 'error');

            return hs_api_responce('414', '玩家信息d11cdd错误 ');
        }
        if (!empty($_member_info['agentgame']) || !empty($_member_info['agent_id'])) {
            return hs_api_responce('416', $this->param['agentgame'].'已有绑定渠道 ');
        }
        if (!isset($this->param['sdk_agent_name']) || empty($this->param['sdk_agent_name'])) {
            return hs_api_responce('417', '申请人信息错误');
        }
        /*user_login*/
        $_u_map['user_login'] = $this->param['sdk_agent_name'];
        $_users_info = \think\Db::name('users')->where($_u_map)->find();
        if (!empty($_users_info) && isset($_users_info['id']) && $_users_info['id']) {
            $this->param['sdk_agent_id'] = $_users_info['id'];
        }

        return true;
    }

    public function checkPayFirst() {
        $_amp['mem_id'] = $this->param['sdk_mem_id'];/* 所有游戏未充值 */
        $_amp['app_id'] = $this->param['game_id'];/* 该游戏未有充值 */
        $_amp['status'] = 2;/* 成功充值 */
        $_re = \think\Db::name('pay')->where($_amp)->find();
        if ($_re !== false && empty($_re)) {
            return true;
        }

        return hs_api_responce('418', '未满足首充条件 ');
    }

    public function Inc($mem_id, $app_id, $amount) {
        $exist = \think\Db::name('gm_mem')->where(array("mem_id" => $mem_id, "app_id" => $app_id))->find();
        if ($exist) {
            $data = array();
            $data['remain'] = $exist['remain'] + $amount;
            $data['total'] = $exist['total'] + $amount;
            $data['update_time'] = time();
            \think\Db::name('gm_mem')->where(array("mem_id" => $mem_id, "app_id" => $app_id))->update($data);
        } else {
            $data = array();
            $data['mem_id'] = $mem_id;
            $data['app_id'] = $app_id;
            $data['remain'] = $amount;
            $data['total'] = $amount;
            $data['update_time'] = time();
            $data['create_time'] = time();
            \think\Db::name('gm_mem')->insert($data);
        }
    }

    public function addIncRecord($give_user_id, $mem_id, $app_id, $amount, $remark) {
        $data = array();
        $data['order_id'] = $this->setorderid($give_user_id);
        $data['admin_id'] = $give_user_id;
        $data['flag'] = 5;/* 代理发放  */
        $data['mem_id'] = $mem_id;
        $data['app_id'] = $app_id;
        $data['money'] = $amount;
        $data['gm_cnt'] = $amount;
        $data['ip'] = $this->get_client_ip();
        $data['remark'] = $remark;
        $data['status'] = 2;
        $data['update_time'] = time();
        $data['create_time'] = time();
        \think\Db::name('gm_given')->insert($data);
    }

    // 生成订单号
    public function setorderid($agent_id = 1) {
        list($usec, $sec) = explode(" ", microtime());
        // 取微秒前3位+再两位随机数+渠道ID后四位
        $orderid = $sec.substr($usec, 2, 3).mt_rand(10, 99).sprintf("%04d", $agent_id % 10000);

        return $orderid;
    }

    public function get_client_ip($type = 0, $adv = false) {
        $type = $type ? 1 : 0;
        static $ip = null;
        if ($ip !== null) {
            return $ip[$type];
        }
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);

        return $ip[$type];
    }
}

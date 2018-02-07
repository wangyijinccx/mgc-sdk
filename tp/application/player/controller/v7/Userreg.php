<?php
/**
 * Userreg.php UTF-8
 * 玩家注册接口
 *
 * @date    : 2016年8月18日下午9:47:10
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : api 2.0
 */
namespace app\player\controller\v7;

use huosdk\common\Commonfunc;
use huosdk\integral\Memitg;
use huosdk\request\Request;

class Userreg extends User {
    function _initialize() {
        parent::_initialize();
    }

    /*
     * 一键注册
     */
    function regOne() {
        $_key_arr = array('app_id', 'client_id', 'from', 'device_id', 'userua');
        $_data = $this->getParams($_key_arr);
        $_username = $this->m_class->genUsername();
        $_agentgame = $this->getVal($_data, 'agentgame', '');
        $_rdata['username'] = $_username;
//         $_rdata['password'] = $this->m_class->authPwd(hs_random(8));
        //$_rdata['password'] = hs_random(8);
        $_rdata['password'] = "";
        $_rdata['agentgame'] = $this->getAgentgame($_agentgame);

        return hs_player_responce(200, '一键注册成功', $_rdata, $this->auth_key);
    }

    /*
     * 普通注册
     */
    function register() {
        $_key_arr = array('app_id', 'client_id', 'from', 'device_id', 'userua', 'username', 'password');
        $_rdata = $this->_reg($_key_arr);

        return hs_player_responce('200', '注册成功', $_rdata, $this->auth_key);
    }

    /*
     * 手机注册
     */
    function regMobile() {
        $_key_arr = array('app_id', 'client_id', 'from', 'device_id', 'userua', 'mobile', 'password',
                          'smstype', 'smscode');
        /* 验证手机短信 */
        $this->checkMobile();
        $this->rq_data['username'] = $this->rq_data['mobile'];
        $_rdata = $this->_reg($_key_arr);
        /* BEGIN 获取积分 ITG_BINDMOBILE */
        $_mitg_class = new Memitg($_rdata['mem_id']);
        $_mitg_class->addbyAction(ITG_BINDMOBILE);

        /* END 获取积分 ITG_BINDMOBILE */

        return hs_player_responce('200', '手机注册成功', $_rdata, $this->auth_key);
    }

    /*
     * 注册函数实体
     */
    private function _reg($_key_arr) {
        \think\Log::write('zzsxxx' . print_r($_key_arr, true), 'error');
        $_data = $this->getParams($_key_arr);
        $_agentgame = $this->getVal($_data, 'agentgame', '');
        $_data['agentgame'] = $this->getAgentgame($_agentgame);
        $_data['ip'] = $this->request->ip();

        // 注册微吼
        $url = 'http://e.vhall.com/api/vhallapi/v2/user/register';
        $username = $_data["username"];
        $wh_user_id = 0;
        $wh_passwd = get_random_password();
        $params = array(
            'third_user_id' => $username,
            'pass' => $wh_passwd,
            'auth_type' => 1,
            'account' => 'v14903519',
            'password' => 'anywn123',
        );
        \think\Log::write('zzsxxx come in 0', 'error');
        $res = Request::httpJsonpost($url, json_encode($params));
        \think\Log::write('zzsxxx come in 2', 'error');
        $user_arr = json_decode($res, true);
        if (200 == $user_arr["code"]) {
            $wh_user_id = $user_arr["data"]["user_id"];
        } else {
            //return hs_player_responce('411', '注册直播观众账号出错');
        }
        $_data["wh_user_id"] = $wh_user_id;
        $_data["wh_passwd"] = $wh_passwd;

        $_mem_info = $this->m_class->regMem($_data);
        if (!empty($_mem_info['p_mem_id'])) {
            $conflict_id = $_mem_info['p_mem_id'];
            Db::table('members')->where('id', $conflict_id)->setField('p_mem_id', 1);
        }
        if (-1 == $_mem_info['id']) {
            return hs_player_responce('411', '用户名不合法或密码不合法');
        }
        if (-3 == $_mem_info['id']) {
            return hs_player_responce('411', '用户名已存在');
        }

        $_rdata = $this->getReturn($_mem_info, $_data['agentgame'], 1);
        $_inapp = Commonfunc::isApp($_data['app_id']);
        if ($_inapp) {
            /* BEGIN 获取积分 ITG_TGUSER */
            if (!empty($_mem_info['parent_mem_id'])) {
                $_mitg_class = new Memitg($_mem_info['parent_mem_id']);
                $_mitg_class->addbyAction(ITG_TGUSER, $_mem_info['id']);
            }
            /* END 获取积分 ITG_TGUSER */
            /* BEGIN 获取积分 ITG_STARTAPP */
            $_mitg_class = new Memitg($_mem_info['id']);
            $_mitg_class->addbyAction(ITG_STARTAPP);
            /* END 获取积分 ITG_STARTAPP */
        }

        return $_rdata;
    }
}

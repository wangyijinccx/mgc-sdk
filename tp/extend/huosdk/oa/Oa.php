<?php
/**
 * Oa.php UTF-8
 *
 *
 * @date    : 2017/5/24 17:58
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : guxiannong <gxn@huosdk.com>
 * @version : HUOOA 1.0
 */
namespace huosdk\oa;

use huosdk\common\Commonfunc;
use think\Db;

class Oa {
    public $oa_conf
        = array(
            "PLAT_ID"           => "1",/* 平台ID */
            "PLAT_SECURE_KEY"   => "",/* 平台的秘钥 */
            "METHOD"            => "POST",/* 提交方式 */
            "SIGN_TYPE"         => "MD5",/* 验证方式 */
            "OA_HOST"           => "",/* 对接服务器 */
            "MEM_REG_URL"       => 'v1/api/user/reg',/* 用户注册 */
            "MEM_LOGIN_URL"     => 'v1/api/user/login',/* 用户登录 */
            "MEM_UPINFO_URL"    => 'v1/api/user/uproleinfo',/* 用户上传角色 */
            "MEM_PAY_URL"       => 'v1/api/user/pay',/* 用户充值 */
            "MEM_UPDATE_URL"    => 'v1/api/user/update',/* 用户修改归属*/
            "GAME_ADD_URL"      => 'v1/api/game/add',/* 添加游戏 */
            "GAME_UPDATE_URL"   => 'v1/api/game/update',/* 修改游戏 */
            "GAME_DELETE_URL"   => 'v1/api/game/delete',/* 删除游戏 */
            "GAME_RESTORE_URL"  => 'v1/api/game/restore',/* 还原已删除游戏 */
            "SERVER_ADD_URL"    => 'v1/api/server/add',/* 添加游戏区服 */
            "SERVER_UPDATE_URL" => 'v1/api/server/update',/* 修改游戏区服 */
            "GM_FIRST_URL"      => 'v1/api/gm/first',/* 首充回调 */
            "GM_FOSTER_URL"     => 'v1/api/gm/foster',/* 扶植回调 */
            "GET_WEBINAR_URL"     => 'v1/api/webinar/get',/* 获取直播间 */
        );

    public function __construct() {
        $_conf_file = CONF_PATH."extra/oa/config.php";
        if (file_exists($_conf_file)) {
            $this->oa_conf = include $_conf_file;
        }
    }

    /**
     * @param string $func
     * @param array  $param
     *
     * @return bool
     */
    public function request($func, array $param) {
        $_param = $param;
        $_param['plat_id'] = $this->oa_conf['PLAT_ID'];
        $_param['timestamp'] = time();
        $_param['sign_type'] = $this->oa_conf['SIGN_TYPE'];
        $_query_str = $this->build_param($_param);
        if (!empty($this->oa_conf[$func])) {
            $_url = $this->oa_conf['OA_HOST'].$this->oa_conf[$func];
        } else {
            return false;
        }
        $_cookie = '';
        $_timeout = 0;
        \think\Log::write($_url, 'error');
        \think\Log::write('OA_Api_'.$_query_str, 'error');
        return \huosdk\request\Request::asyncRequst($_url, $_query_str, $_cookie, $_timeout);
    }


    public function requestForWebinar($func, array $param) {
        $_param = $param;
        $_param['plat_id'] = $this->oa_conf['PLAT_ID'];
        $_param['timestamp'] = time();
        $_param['sign_type'] = $this->oa_conf['SIGN_TYPE'];
        $_param['sign'] = $this->getSign($_param, $this->oa_conf['PLAT_SECURE_KEY']);
        if (!empty($this->oa_conf[$func])) {
            $_url = $this->oa_conf['OA_HOST'].$this->oa_conf[$func];
        } else {
            return false;
        }
        $_data_string = json_encode($_param);
        \think\Log::write($_url, 'error');
        \think\Log::write($_param, 'error');
        return \huosdk\request\Request::httpJsonpost($_url,$_data_string);
       // return \huosdk\request\Request::asyncRequst($_url, $_query_str, $_cookie, $_timeout);
    }

    public function build_param(array $param) {
        $_param = $param;
        $_param['sign'] = $this->getSign($param, $this->oa_conf['PLAT_SECURE_KEY']);

        return Commonfunc::createLinkstring($_param);
    }

    public function getSign($param, $key = '') {
        $_param = Commonfunc::argSort($param);
        $_str = Commonfunc::createLinkstring($_param);
        $_sign = md5($_str.'&key='.$key);

        return $_sign;
    }

    public function getAgentnamebById($agent_id) {
        if (empty($agent_id)) {
            return '';
        }
        $_map['id'] = $agent_id;
        $_rs = Db::name('users')->where($_map)->cache($agent_id, 86400)->value('user_login');
        if (empty($_rs)) {
            return '';
        }

        return $_rs;
    }

    public function checkSign($param = []) {
        if (!isset($param['sign'])) {
            return hs_api_responce('404', '签名错误');
        }
        $_sign = $param['sign'];
        $_param = $param;
        unset($_param['sign']);
        if (isset($_param['version'])) {
            unset($_param['version']);
        }
        $_param = Commonfunc::argSort($_param);
        $_sign_str = Commonfunc::createLinkstring($_param);
        $_verify_sign = md5($_sign_str.'&key='.$this->oa_conf['PLAT_SECURE_KEY']);
        if ($_verify_sign != strtolower($_sign)) {
            return hs_api_responce('404', '签名错误');
        }

        return true;
    }

    public function getUersnameById($mem_id) {
        if (empty($mem_id)) {
            return '';
        }
        $_map['id'] = $mem_id;
        $_rs = Db::name('members')->where($_map)->cache($mem_id, 86400)->value('username');
        if (empty($_rs)) {
            return '';
        }

        return $_rs;
    }
}
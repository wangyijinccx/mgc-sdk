<?php
/**
 * Member.php UTF-8
 * 玩家类
 *
 * @date    : 2016年11月11日下午3:56:17
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2016年11月11日下午3:56:17
 */
namespace huosdk\player;

use think\Config;
use think\Db;

class Member {
    private $mem_id;

    /**
     * Member constructor.
     *
     * @param int    $mem_id   玩家ID
     * @param string $username 玩家名称
     */
    public function __construct($mem_id = 0, $username = '') {
        if (empty($mem_id)) {
            $this->setMemidfromUsername($username);
        } else {
            $this->mem_id = $mem_id;
        }
    }

    /**
     * 设置mem_id
     *
     * @param $mem_id INT 玩家ID
     */
    public function setMemid($mem_id) {
        $this->mem_id = $mem_id;
    }

    /**
     * 通过$username设置$mem_id
     *
     * @param $username string 玩家账号
     */
    public function setMemidfromUsername($username) {
        if (empty($username)) {
            $this->mem_id = 0;

            return;
        }
        $_map['username'] = $username;
        $_mem_id = Db::name('members')->where($_map)->value('id');
        if (empty($_mem_id)) {
            $this->mem_id = 0;

            return;
        }
        $this->mem_id = $_mem_id;
    }

    /**
     * 获取mem_id
     */
    public function getMemid() {
        return $this->mem_id;
    }

    public function checkRegdata($data) {
        if (empty($data['username'])) {
            return -1;
        }
        if (empty($data['password'])) {
            return -1;
        }

        return 1;
    }

    public function checklogindata($data) {
        if (empty($data['username'])) {
            $_mem_info['id'] = -411;

            return $_mem_info;
        }
        if (empty($data['password'])) {
            $_mem_info['id'] = -412;

            return $_mem_info;
        }


        $_sms_class = new \huosdk\sms\Sms();
        $_is_mobile = $_sms_class->checkMoblie($data['username']);
        if ($_is_mobile){
            $_map['username|mobile'] = $data['username'];
        }else{
            $_map['username'] = $data['username'];
        }
        $_field = [
            'id',
            'username',
            'email',
            'mobile',
            'nickname',
            'agent_id',
            'password',
            'reg_time',
            'status',
            'portrait',//20170523新增返回
        ];
        $_mem_info = Db::name('members')->where($_map)->field($_field)->find();
        if (empty($_mem_info)) {
            $_mem_info['id'] = -411;

            return $_mem_info;
        }
        if (3 == $_mem_info['status']) {
            $_mem_info['id'] = -3;

            return $_mem_info;
        }
        if (!$this->comparePwd($data['password'], $_mem_info['password'])) {
            $_mem_info['id'] = -412;

            return $_mem_info;
        }
        unset($_mem_info['password']);

        return $_mem_info;
    }

    /* 比较密码 */
    public function comparePwd($password, $password_in_db) {
        return $this->authPwd($password) == $password_in_db;
    }

    /* 正确获取值 若不存在则使用默认 */
    public function getVal($data, $key, $default = '') {
        if (empty($key) || empty($data) || !isset($data[$key])) {
            return $default;
        }

        return $data[$key];
    }

    public function regMem($data) {
        // 查询玩家账号是否存在
        $_rs = $this->checkRegdata($data);
        if ($_rs <= 0) {
            /* 用户名不合法 */
            $_mem_info['id'] = -1;

            return $_mem_info;
        }
        $_rs = $this->checkUsername($data['username']);
        if ($_rs > 0) {
            /* 用户名已经存在 */
            $_mem_info['id'] = -3;

            return $_mem_info;
        }
        if (!empty($data['introducer'])) {
            $_rs = $this->checkUsername($data['introducer']);
            if ($_rs) {
                $int_data = $data;
                $int_data['username'] = $data['introducer'];
                $int_data['password'] = 'agent123456';
                $int_data['mem_id'] = $_rs;
                $data['agent_id'] = $this->genAgents($int_data);
            } else {
                $_mem_info['id'] = -4;

                return $_mem_info;
            }
        }
        $_mem_info = $this->regMemData($data);

        return $_mem_info;
    }

    public function regMemData($data) {
        $_mem_data['username'] = $data['username'];
        $_mem_data['password'] = $this->authPwd($data['password']);
        $_mem_data['pay_pwd'] = $_mem_data['password'];
        $_mem_data['email'] = $this->getVal($data, 'email', '');
        $_mem_data['mobile'] = $this->getVal($data, 'mobile', '');
        $_mem_data['bindmobile'] = $_mem_data['mobile'];
        if (!empty($_mem_data['mobile'])) {
            /* Added by wuyonghong BEGIN 2017-06-20 ISSUES:2738 手机登陆 */
            $_mem_data['username'] = $this->genUsername();

            /* BEGIN 2017-06-20 添加手机号登陆 已注册的手机号 不能再次注册 */
            $_check_mobile_map['bindmobile'] = $_mem_data['mobile'];
            $_cnt = Db::name('members')->where($_check_mobile_map)->count();
            if ($_cnt > 0){
                $_mem_info['id'] = -3;
                return $_mem_info;
            }
            /* END 2017-06-20 ISSUES:2738 */
        }
        if (!empty($data['introducer'])) {
            $_mem_data['introducer'] = $data['introducer'];
        }
        $_mem_data['nickname'] = $this->getVal($data, 'nickname', $_mem_data['username']);
        $_mem_data['from'] = $this->getVal($data, 'from', 0);
        $_mem_data['imei'] = $this->getVal($data, 'device_id', '');
        $_mem_data['agentgame'] = $this->getVal($data, 'agentgame', 'default');
        $_mem_data['app_id'] = $this->getVal($data, 'app_id', 0);
        $_mem_data['agent_id'] = $this->getVal($data, 'agent_id', 0);
        if (empty($_mem_data['agent_id']) && 'default' != $_mem_data['agentgame'] && !empty($_mem_data['agentgame'])) {
            $_a_class = new \huosdk\agent\Agent(0, $_mem_data['agentgame']);
            $_mem_data['agent_id'] = $_a_class->getAgentid();
        }
        $_mem_data['status'] = $this->getVal($data, 'status', 2);
        $_mem_data['reg_time'] = $this->getVal($data, 'reg_time', time());
        $_mem_data['update_time'] = $this->getVal($data, 'update_time', $_mem_data['reg_time']);
        $_mem_data['regist_ip'] = $this->getVal($data, 'ip', '');
        // 注册微吼账号
        $_mem_data['wh_user_id'] = $this->getVal($data, 'wh_user_id', 0);
        $_mem_data['wh_passwd'] = $this->getVal($data, 'wh_passwd', '12345678');
        // 查找买量和军团冲突
        $conflict_mem = $this->getRepeat($_mem_data['agent_id'], $_mem_data['imei']);
        if (!empty($conflict_mem)) {
            $_mem_data['p_mem_id'] = $conflict_mem['id'];
            Db::table("c_members")
                ->where("id", $conflict_mem['id'])
                ->setField('p_mem_id', 1);
        }
        $_mem_id = Db::name('members')->insertGetId($_mem_data);
        if (!$_mem_id) {
            $_mem_info['id'] = -1000;

            return $_mem_info;
        }
        $_mem_info['parent_mem_id'] = 0;
        if (!empty($_mem_data['agent_id'])) {
            $_ma_class = new MemAgent($_mem_id);
            $_mem_info['parent_mem_id'] = $_ma_class->setParent($_mem_data['agent_id']);
            /* 异步调起推广员统计处理 */
            $_oa_class = new \huosdk\agent\Agentoa($_mem_data['agent_id']);
            $_request_data=$_mem_data;
            $_request_data['mem_id']=$_mem_id;
            $_request_data['id']=$_mem_id;
            $_request=$_oa_class->request_reg_agent_oa($_request_data);
        }
        $_mem_info['id'] = $_mem_id;
        $_mem_info['username'] = $_mem_data['username'];
        $_mem_info['email'] = $_mem_data['email'];
        $_mem_info['mobile'] = $_mem_data['mobile'];
        /* Modified by wuyonghong BEGIN 2017-06-20 ISSUES:2738 手机登陆 */
        $_mem_info['bindmobile'] = $_mem_data['mobile'];
        /* END 2017-06-20 ISSUES:2738 */
        $_mem_info['nickname'] = $_mem_data['nickname'];
        $_mem_info['agent_id'] = $_mem_data['agent_id'];
        $_mem_info['status'] = $_mem_data['status'];
        $_mem_info['reg_time'] = $_mem_data['reg_time'];
	    $_mem_info['do_time'] = $_mem_data['reg_time'];
        $_mem_info['portrait'] = '';

        return $_mem_info;
    }

    private function getRepeat($c_agent_id, $imei) {
        // 先根据device_id查询是否有重复
        if (empty($c_agent_id)) {
            return null;
        }
        $map['imei'] = $imei;
        $res = Db::table("c_members")->where($map)
            ->order('id', 'asc')
            ->limit(1)
            ->select();
        if (!empty($res)) {
            $mem = $res[0];
            $agent_id = $mem["agent_id"];
            $agent = Db::table("c_users")->where('id', $agent_id)->find();
            if (empty($agent)) {
                return null;
            }
            $c_agent = Db::table("c_users")->where('id', $c_agent_id)->find();
            if (empty($c_agent)) {
                return null;
            }
            if ($agent["type_extend"] == $c_agent["type_extend"]) {
                return null;
            }
            return $mem;
        }
        return null;
    }

    public function loginMem($data) {
        // 查询玩家账号是否存在
        $_mem_info = $this->checklogindata($data);

        return $_mem_info;
    }
    /* Added by wuyonghong BEGIN 2017-06-20 ISSUES:2738 手机登陆 */
    /**
     * @param array $data
     *
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     */
    public function loginMobile($data = []) {
        if (empty($data['username'])) {
            $_mem_info['id'] = -411;

            return $_mem_info;
        }
        if (empty($data['password'])) {
            $_mem_info['id'] = -412;

            return $_mem_info;
        }
        $_map['bindmobile'] = $data['username'];
        $_field = [
            'id',
            'username',
            'email',
            'mobile',
            'nickname',
            'agent_id',
            'password',
            'reg_time',
            'status',
            'portrait',//20170523新增返回
        ];
        $_mem_info = Db::name('members')->where($_map)->field($_field)->select();
        if (empty($_mem_info)) {
            $_mem_info['id'] = -411;

            return $_mem_info;
        }
        $_mem_info_array = [];
        $_status = 0;
        foreach ($_mem_info as $_val) {
            if (3 == $_val['status']) {
                $_status = -3;
                continue;
            }
            if (!$this->comparePwd($data['password'], $_val['password'])) {
                $_status = -412;
                continue;
            }
            unset($_val['password']);
            $_mem_info_array[] = $_val;
        }
        if (count($_mem_info_array) <= 0) {
            $_mem_info['id'] = $_status;
            return $_mem_info;
        }

        return $_mem_info_array;
    }
    /* END 2017-06-20 ISSUES:2738 */

    public function loginOauth($data) {
        // 获取第三方信息
        $_map['openid'] = $data['openid'];
        $_map['from'] = $data['userfrom'];
        $_oauth_data = Db::name('mem_oauth')->where($_map)->find();
        $_field = [
            'id',
            'username',
            'email',
            'mobile',
            'nickname',
            'agent_id',
            'reg_time',
            'status',
            'portrait',//20170523新增返回
        ];
        $_mem_info = array();
        if (empty($_oauth_data)) {
            // 需插入oauth表
            $_oauth_data['from'] = $data['userfrom'];
            $_oauth_data['name'] = $this->getVal($data, 'nickname', '');
            $_oauth_data['head_img'] = $this->getVal($data, 'head_img', '');
            $_oauth_data['create_time'] = time();
            $_oauth_data['last_login_time'] = time();
            $_oauth_data['last_login_ip'] = $data['ip'];
            $_oauth_data['status'] = 2;
            $_oauth_data['mem_id'] = 0;
            $_oauth_data['access_token'] = $data['access_token'];
            $_oauth_data['expires_date'] = $data['expires_date'];
            $_oauth_data['openid'] = $data['openid'];
            $_oauth_data['id'] = Db::name('mem_oauth')->insertGetId($_oauth_data);
            if (0 < $_oauth_data['id']) {
                if (1 == $data['userfrom']) {
                    $_mem_info['status'] = 1;
                } else {
                    $_mem_info['status'] = 2;
                }
                $_mem_info['username'] = $this->genUsername();
                $_mem_info['password'] = $this->authPwd($data['access_token']);
                $_mem_info['pay_pwd'] = $_mem_info['password'];
                $_mem_info['mobile'] = '';
                $_mem_info['email'] = '';
                $_mem_info['nickname'] = $this->getVal($data, 'nickname', $_mem_info['username']);
                $_mem_info['from'] = intval($data['from']);
                $_mem_info['imei'] = $data['device_id'];
                $_mem_info['agentgame'] = $data['agentgame'];
                $_mem_info['app_id'] = $data['app_id'];
                $_mem_info['agent_id'] = isset($data['agent_id']) ? $data['agent_id'] : 0;
                $_mem_info['reg_time'] = time();
                $_mem_info['update_time'] = $_mem_info['reg_time'];
                if (empty($_mem_info['agent_id']) && 'default' != $_mem_info['agentgame']
                    && !empty(
                    $_mem_info['agentgame']
                    )
                ) {
                    $_a_class = new \huosdk\agent\Agent(0, $_mem_info['agentgame']);
                    $_mem_info['agent_id'] = $_a_class->getAgentid();
                }
                $_mem_info['regist_ip'] = $data['ip'];
                $_mem_info['id'] = Db::name('members')->insertGetId($_mem_info);
                if (!$_mem_info['id']) {
                    $_mem_info['id'] = -1000;

                    return $_mem_info;
                }
                /* 有推广员信息异步更新 */
                if($_mem_info['agent_id']){
                    $_oa_class = new \huosdk\agent\Agentoa($_mem_info['agent_id']);
                    $_request=$_oa_class->request_reg_agent_oa($_mem_info);
                }
                $_oauth_data['mem_id'] = $_mem_info['id'];
                Db::name('mem_oauth')->update($_oauth_data);
                foreach ($_field as $_val) {
                    if (array_key_exists($_val, $_mem_info)) {
                        $_rdata[$_val] = $_mem_info[$_val];
                    } else {
                        $_rdata[$_val] = '';
                    }
                }
                $_rdata['flag'] = 1;

                return $_rdata;
            } else {
                $_mem_info['id'] = -1000;

                return $_mem_info;
            }
        } else {
            if ($_oauth_data['access_token'] != $data['access_token']) {
                /* 已超过有效期 */
                $_mem_info['id'] = -418;

                return $_mem_info;
            }
            $_mem_info = DB::name('members')->where(
                array(
                    'id' => $_oauth_data['mem_id']
                )
            )->field($_field)->find();
            $_oauth_data['last_login_time'] = time();
            $_oauth_data['last_login_ip'] = $data['ip'];
            Db::name('mem_oauth')->where($_map)->update($_oauth_data);

            return $_mem_info;
        }
    }

    /**
     *
     * 密码加密函数
     *
     * @param        $pw       密码
     * @param string $authcode 加密字符串
     *
     * @return string
     */
    public function authPwd($pw, $authcode = '') {
        if (empty($authcode)) {
            $authcode = Config::get('config.HSAUTHCODE');
        }
        $_result = md5(md5($authcode.$pw).$pw);

        return $_result;
    }

    public function userAuthPassword($pw, $authcode = '') {
        if (empty($authcode)) {
            $authcode = Config::get('config.HSAUTHCODE');
        }
        $result = "###".md5(md5($authcode.$pw));

        return $result;
    }


    public function checkUsername($username) {
        if (empty($username)) {
            return 0;
        }
        $this->setMemidfromUsername($username);

        return $this->mem_id;
    }

    public function genUsername() {
        $_basenum = 10000;
        // 生成用户名
        $_min = DB::name('mem_base')->min('base');
        $_cnt = DB::name('mem_base')->where(
            array(
                'base' => $_min
            )
        )->count('id');
        $_limit = rand(0, $_cnt);
        $_map['base'] = $_min;
        $_sqllimit = "'".$_limit.",1'";
        $_sqllimit = "$_limit,1";
        $_base_data = DB::name('mem_base')->where($_map)->limit($_sqllimit)->select();
        $_uid = $_base_data[0]['id'];
        $_rs = DB::name('mem_base')->where(
            array(
                'id' => $_uid
            )
        )->setInc('base', 1);
        if (false != $_rs) {
            $_username = $_basenum * $_min + $_uid;
        }
        $_mem_id = $this->checkUsername($_username);
        $_i = 0;
        // 存在用户一直向下执行
        while ($_mem_id > 0 && $_i < 20) {
            $_i++;
            $_rs = DB::name('mem_base')->where(
                array(
                    'id' => $_uid
                )
            )->setInc('base', 1);
            if (false != $_rs) {
                $_username = $_basenum * ($_min + $_i) + $_limit;
            }
            $_mem_id = $this->checkUsername($_username);
        }

        return $_username;
    }

    /**
     * 玩家生成渠道
     *
     * @return bool|int|mixed|string
     */
    public function genAgents($data) {
        $_mem_id = $data["mem_id"];
        if (empty($_mem_id)) {
            return false;
        }
        $_user_map['mem_id'] = $_mem_id;
        $_id = Db::name('users')->where($_user_map)->value('id');;
        if (!empty($_id)) {
            return $_id;
        }
        /* 生成渠道数据 */
        $_u_data['user_login'] = $data["username"];
        $_u_data['user_pass'] = $data["mem_id"].md5($data["username"]);
        $_u_data['user_nicename'] = $_u_data['user_login'];
        $_u_data['create_time'] = date('Y-m-d H:i:s');
        $_u_data['user_status'] = 3;
        $_u_data['mem_id'] = $_mem_id;
        $_id = Db::name('users')->insertGetId($_u_data);
        if (!$_id) {
            return 0;
        }

        return $_id;
    }
}

<?php
/**
 * OagmController.class.php UTF-8
 *
 *
 * @date    : 2017/6/16 21:02
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : guxiannong <gxn@huosdk.com>
 * @version : HUOOA 1.0
 */
namespace Tui\Controller;

use Common\Controller\AdminbaseController;

class OagmController extends AdminbaseController {
    function _initialize() {
        parent::_initialize();
    }

    function _game(
        $option = true, $status = null, $is_delete = null, $is_sdk = null, $is_app = null, $classfy = 3,
        $game_flag = false
    ) {
        $cates = array(
            "0" => "选择游戏",
        );
        if ($status) {
            $where['status'] = 2;
        }
        if ($is_delete) {
            $where['is_delete'] = 2;
        }
        if ($is_sdk) {
            $where['is_own'] = 2;
        }
        if ($is_app) {
            $where['is_app'] = 2;
        }
        if ($classfy) {
            if (3 == substr($classfy, 0, 1)) {
                $where['_string'] = " classify=3 OR (classify BETWEEN 300 AND 399)";
            } else {
                $where['classify'] = $classfy;
            }
        }
        if (3 <= $this->role_type) {
            $agents = $this->_getOwnerAgents();
            $apparr = M('agent_game')->where(array('agent_id' => array('in', $agents)))->getField('app_id', true);
            if ($apparr) {
                $where['id'] = array('in', implode(',', $apparr));
            }
        }
        if ($game_flag) {
            $_group = "game_id";
            $games = M('game')->where($where)->group($_group)->getField("game_id id,name gamename", true);
        } else {
            $games = M('game')->where($where)->getField("id id,name gamename");
        }
        if ($option && $games) {
            $games = $cates + $games;
        }
        $this->assign("games", $games);
    }

    public function first() {
        $this->_game(true, null, null, null, null, null);
        $hf_filter_obj = new \Huosdk\UI\Filter();
        $this->assign("parent_agent_select_with_official", $hf_filter_obj->parent_agent_select_with_official());
        $this->assign("agent_select_with_official", $hf_filter_obj->agent_select_with_official());
        /* 首充 */
        $model = M('gm_log');
        $where = array();
        $where["awr.type_id"] = "1";
        $hs_where_obj = new \Huosdk\Where();
        $hs_where_obj->get_simple($where, "agent_id", "awr.agent_id");
        $hs_where_obj->get_simple($where, "oa_gm_id", "awr.oa_gm_id");
        $hs_where_obj->get_simple($where, "node_name", "awr.node_name");
        $hs_where_obj->get_simple($where, "status", "awr.status");
        $hs_where_obj->get_simple($where, "check_status", "awr.check_status");
        $hs_where_obj->get_simple($where, "role_name", "awr.role_name");
        $hs_where_obj->get_simple($where, "username", "awr.username");
        $hs_where_obj->get_simple($where, "game_id", "awr.game_id");
        $hs_where_obj->get_simple($where, "ser_code", "awr.ser_code");
        $hs_where_obj->get_simple($where, "mem_id", "awr.mem_id");
        $start_time = trim(I('get.start_time'));
        $end_time = trim(I('get.end_time'));
        if (!empty($start_time) && empty($end_time)) {
            $where['awr.create_time'] = ['egt', strtotime($start_time)];
        }
        if (!empty($end_time) && empty($start_time)) {
            $where['awr.create_time'] = ['lt', strtotime($end_time.' +1 day')];
        }
        if (!empty($end_time) && !empty($start_time)) {
            $where['awr.create_time'] = array(array('gt', strtotime($start_time)),
                                              array('lt', strtotime($end_time.' +1 day')));
        }
        if (isset($_GET['agentname']) && ($_GET['agentname'])) {
            $where_extra['u.user_login'] = $_GET['agentname'];
        }
        if (isset($_GET['agentnickname']) && ($_GET['agentnickname'])) {
            if ($_GET['agentnickname'] == "官包") {
                $where_extra['_string'] .= " AND (p.agent_id = 0 )";
//                $where_extra['p.agent_id'] = array("eq","0");
            } else {
                $where_extra['u.user_nicename'] = $_GET['agentnickname'];
            }
        }
        if (isset($_GET['parent_agentname']) && ($_GET['parent_agentname'])) {
            if ($_GET['parent_agentname'] == "官方渠道") {
                $where_extra['_string'] .= " AND (u2.user_nicename IS NULL )";
            } else {
                $where_extra['u2.user_nicename'] = $_GET['parent_agentname'];
            }
        }
        //父渠道
        if (isset($_GET['parent_agent_id']) && ($_GET['parent_agent_id'])) {
            $where_extra['u.ownerid'] = $_GET['parent_agent_id'];
        }
        //渠道
        if (isset($_GET['agent_id']) && ($_GET['agent_id'])) {
            if (1 == $_GET['agent_id']) {
                $where_extra['m.agent_id'] = 0;
            } else {
                $where_extra['m.agent_id'] = $_GET['agent_id'];
            }
        }
        $count = $model
            ->field('awr.*,u.user_nicename,u.user_login,g.name as game_name')
            ->alias('awr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u ON u.id=awr.agent_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=awr.game_id")
            ->where($where)
            ->count();
        $page = $this->page($count, $this->row);
        $items = $model
            ->field('awr.*,u.user_nicename,u.user_login,g.name as game_name')
            ->alias('awr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u ON u.id=awr.agent_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=awr.game_id")
            ->where($where)
            ->order("awr.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->select();
        $where_sums = $where;
        if (!isset($where_sums['awr.status'])) {
            $where_sums['awr.status'] = 2;
        }
        $sums_money = $model
            ->alias('awr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u ON u.id=awr.agent_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=awr.game_id")
            ->where($where_sums)
            ->order("awr.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->sum('money');
        $hs_ff_obj = new \Huosdk\UI\Filter();
        $this->assign("agent_select", $hs_ff_obj->agent_select());
        $this->assign("formget", $_GET);
        $this->assign("items", $items);
        $this->assign("sums_money", $sums_money);
        $this->assign("total_rows", $count);
        $this->assign("Page", $page->show('Admin'));
        $this->assign("current_page", $page->GetCurrentPage());
        $this->_paystatus();
        $this->display();
    }

    function _paystatus() {
        $cates = array(
            ""  => "全部",
            "1" => "待处理",
            "2" => "已发放",
            "3" => "拒绝发放",
        );
        $this->assign("paystatuss", $cates);
    }

    public function foster() {
        $this->_game(true, null, null, null, null, null);
        $hf_filter_obj = new \Huosdk\UI\Filter();
        $this->assign("parent_agent_select_with_official", $hf_filter_obj->parent_agent_select_with_official());
        $this->assign("agent_select_with_official", $hf_filter_obj->agent_select_with_official());
        /* 扶植 */
        $model = M('gm_log');
        $where = array();
        $where["awr.type_id"] = "2";
        $hs_where_obj = new \Huosdk\Where();
        $hs_where_obj->get_simple($where, "agent_id", "awr.agent_id");
        $hs_where_obj->get_simple($where, "status", "awr.status");
        $hs_where_obj->get_simple($where, "check_status", "awr.check_status");
        $hs_where_obj->get_simple($where, "oa_gm_id", "awr.oa_gm_id");
        $hs_where_obj->get_simple($where, "node_name", "awr.node_name");
        $hs_where_obj->get_simple($where, "role_name", "awr.role_name");
        $hs_where_obj->get_simple($where, "username", "awr.username");
        $hs_where_obj->get_simple($where, "game_id", "awr.game_id");
        $hs_where_obj->get_simple($where, "ser_code", "awr.ser_code");
        $hs_where_obj->get_simple($where, "mem_id", "awr.mem_id");
        $start_time = trim(I('get.start_time'));
        $end_time = trim(I('get.end_time'));
        if (!empty($start_time) && empty($end_time)) {
            $where['awr.create_time'] = ['egt', strtotime($start_time)];
        }
        if (!empty($end_time) && empty($start_time)) {
            $where['awr.create_time'] = ['lt', strtotime($end_time.' +1 day')];
        }
        if (!empty($end_time) && !empty($start_time)) {
            $where['awr.create_time'] = array(array('gt', strtotime($start_time)),
                                              array('lt', strtotime($end_time.' +1 day')));
        }
        $count = $model
            ->field('awr.*,u.user_nicename,u.user_login,g.name as game_name')
            ->alias('awr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u ON u.id=awr.agent_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=awr.game_id")
            ->where($where)
            ->count();
        $page = $this->page($count, $this->row);
        $items = $model
            ->field('awr.*,u.user_nicename,u.user_login,g.name as game_name')
            ->alias('awr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u ON u.id=awr.agent_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=awr.game_id")
            ->where($where)
            ->order("awr.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->select();
        $where_sums = $where;
        if (!isset($where_sums['awr.status'])) {
            $where_sums['awr.status'] = 2;
        }
        $sums_money = $model
            ->alias('awr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u ON u.id=awr.agent_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=awr.game_id")
            ->where($where_sums)
            ->order("awr.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->sum('money');
        $hs_ff_obj = new \Huosdk\UI\Filter();
        $this->assign("agent_select", $hs_ff_obj->agent_select());
        $this->assign("formget", $_GET);
        $this->assign("items", $items);
        $this->assign("sums_money", $sums_money);
        $this->assign("Page", $page->show('Admin'));
        $this->_paystatus();
        $this->display();
    }

    public function status() {
        /* 这里进行发币处理 */
        $id = I('id', 0);
        $status = I('status', 0);
        $type = I('type', 0);
        $reason = I('reason', 0);
        $show_info = I('show_info', 0);
        $back_url = I('back_url', 0);
        $paypwd = I('paypwd', 0);
        if (empty($id) || empty($status)) {
            $this->ajaxReturn(array("error" => "1", "msg" => "参数有误"));
            exit;
        }
        $_map = [];
        $_map['id'] = $id;
        $_gm_model = M('gm_log');
        $_mg_info = $_gm_model->where($_map)->find();
        if (2 == $_mg_info['status'] || 3 == $_mg_info['status']) {
            $this->ajaxReturn(array(array("error" => "0", "msg" => "处理成功")));
            exit;
        }
        if (1 == $show_info) {
            $_mg_info['member_name'] = $_mg_info['role_name'];
            $_game_model = M('game');
            $_game_map['id'] = $_mg_info['game_id'];
            $_game_info = $_game_model->where($_game_map)->find();
            $_mg_info['game_name'] = $_game_info['name'];
            $_mg_info['back_url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].urldecode($back_url);
            $this->assign("from_data", $_mg_info);
            $this->display();
            exit;
        }
        if (empty($_mg_info)) {
            $this->ajaxReturn(array("error" => "1", "msg" => "未找到对应信息"));
            exit;
        }
        if (!isset($_mg_info['game_id']) || empty($_mg_info['game_id'])) {
            $this->ajaxReturn(array("error" => "1", "msg" => "游戏id错误"));
        }
        if (!isset($_mg_info['money']) || empty($_mg_info['money'])) {
            $this->ajaxReturn(array("error" => "1", "msg" => "金额有误"));
        }
        if (!isset($_mg_info['mem_id']) || empty($_mg_info['mem_id'])) {
            $this->ajaxReturn(array("error" => "1", "msg" => "玩家信息有误"));
        }
        if (empty($type)) {
            if (!isset($_mg_info['type_id'])) {
                $this->ajaxReturn(array("error" => "1", "msg" => "处理类型错误"));
                exit;
            }
            $type = $_mg_info['type_id'];
        }
        if (2 == $status) {
            $admin_id = get_current_admin_id();
            $hs_password_obj = new \Huosdk\Password();
            $pass_match = $hs_password_obj->checkAdminPaypwd($admin_id, $paypwd);
            if (!$pass_match) {
                if (1 == $type) {
                    /* 一块钱的还要不要输密码 */
                } else {
                    $this->ajaxReturn(array("error" => "1", "msg" => "二级密码错误"));
                    exit;
                }
            }
            $this->giveMemberMoney(
                $admin_id, $_mg_info['mem_id'], $_mg_info['game_id'], $_mg_info['money'] * C('G_RATE'), $reason
            );
        }
        //$_gm_model = M('gm_log');
        $_gm_update_arr = [];
        $_gm_update_arr['status'] = $status;
        $_gm_update_arr['update_time'] = time();
        if (2 == $status) {
            $_gm_update_arr['check_reason'] = $reason.'<br/>'.$_mg_info['check_reason'];
        } elseif (3 == $status) {
            $_gm_update_arr['fail_reason'] = $reason.'<br/>'.$_mg_info['fail_reason'];
        }
        $_save_re = M('gm_log')->where($_map)->save($_gm_update_arr);
        if (false === $_save_re) {
            $this->ajaxReturn(array("error" => "1", "msg" => "更新处理失败"));
        }
        if (1 == $type) { /* 首充成功后修改玩家归属 */
            $_us_map = [];
            $_us_map['agent_id'] = $_mg_info['agent_id'];
            $_us_map['app_id'] = $_mg_info['game_id'];
            $_agent_game_model = M('agent_game');
            $_agent_info = $_agent_game_model->where($_us_map)->find();
            if (!empty($_agent_info) && isset($_agent_info['agentgame']) && $_agent_info['agentgame']) {
                $_up_data = [];
                $_up_data['agentgame'] = $_agent_info['agentgame'];
                $_up_data['agent_id'] = $_us_map['agent_id'];
                $_up_data['update_time'] = time();
                $_mem_map = [];
                $_mem_map['id'] = $_mg_info['mem_id'];
                $_u_re = M('members')->where($_mem_map)->save($_up_data);
                if (empty($_u_re)) {
                    \Think\Log::write(json_encode($_mem_map).json_encode($_up_data), 'error');
                }
            } else {
                \Think\Log::write($_agent_game_model->getLastSql(), 'error');
            }
        }
        $do_request = \Huosdk\Request::oaGmCallBack(
            $_mg_info['oa_gm_id'], $_mg_info['game_id'], $status, $type, $reason
        );
        if ($do_request) {
            \Think\Log::write(is_array($do_request) ? json_encode($do_request) : $do_request, 'debug');
        }
        $this->ajaxReturn(array("error" => "0", "msg" => "处理成功"));
    }

    /* 引入代码 */
    public function giveMemberMoney($admin_id, $mem_id, $app_id, $amount, $remark) {
        if (!(is_numeric($amount) && $amount > 0)) {
            $this->ajaxReturn(array("error" => "1", "msg" => "金额有误"));
            exit;
        }
        $hs_gmbalance_obj = new \Huosdk\GmBalance();
        $hs_gmbalance_obj->Inc($mem_id, $app_id, $amount);
        $hs_gmbalance_obj->addIncRecord($admin_id, $mem_id, $app_id, $amount, $remark);
    }

    public function AdminForAgent() {
        $agent_id = get_current_admin_id();
        $where = array("agc.agent_id" => $agent_id);
        $hs_where_obj = new \Huosdk\Where();
        $hs_where_obj->time($where, "agc.create_time");
        $this->_where_order_id($where, "agc.order_id");
        $this->_where_agent_name($where, "u.user_login");
        $this->_where_admin_name($where, 'agc.admin_id', 'ua.user_login');
        $count = M('gm_agentcharge')
            ->alias('agc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=agc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=agc.agent_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users ua ON ua.id=agc.admin_id")
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $items = M('gm_agentcharge')
            ->field("agc.*,u.user_login as agentname,ua.user_login as adminname")
            ->alias('agc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=agc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=agc.agent_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users ua ON ua.id=agc.admin_id")
            ->where($where)
            ->order("agc.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->select();
        foreach ($items as $k => $item) {
            if ($item['admin_id'] == 0) {
                $items[$k]['adminname'] = "平台官方";
            }
        }
        $this->assign("orders", $items);
        $sums = M('gm_agentcharge')
            ->field("sum(agc.money) as total")
            ->alias('agc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=agc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=agc.agent_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users ua ON ua.id=agc.admin_id")
            ->where($where)
            ->select();
        $this->assign("sums", $sums[0]['total']);
        $this->assign("formget", $_GET);
        $this->assign("Page", $page->show('Admin'));
        $this->assign("current_page", $page->GetCurrentPage());
        $this->display();
    }

    public function _where_agent_name(&$where, $field) {
        $name = "agentname";
        if (isset($_GET[$name]) && $_GET[$name]) {
            $agentname = $_GET[$name];
            $where[$field] = array("like", "%$agentname%");
        }
    }

    public function _where_admin_name(&$where, $field1, $field2) {
        $name = "adminname";
        if (isset($_GET[$name]) && $_GET[$name]) {
            $adminname = trim($_GET[$name]);
            if ($adminname == "平台官方") {
                $where[$field1] = array("eq", "0");
            } else {
                $where[$field2] = array("like", "%$adminname%");
            }
        }
    }

    public function AgentForSub() {
        $agent_id = get_current_admin_id();
        $obj = new \Huosdk\Account();
        $info = $obj->get_agent_info_by_id($agent_id);
        $agent_name = $info['user_login'];
        $where = array("agc.admin_id" => $agent_id);
        $hs_where_obj = new \Huosdk\Where();
        $hs_where_obj->time($where, "agc.create_time");
        $name = "orderid";
        if (isset($_GET[$name]) && $_GET[$name]) {
            $orderid = $_GET[$name];
            $where['agc.order_id'] = array("eq", "$orderid");
        }
//        $name="agentname";
//        if(isset($_GET[$name])&&$_GET[$name]){
//            $agentname=$_GET[$name];
//            $where['u.user_login']=array("like","%$agentname%");
//        }
        $name = "subname";
        if (isset($_GET[$name]) && $_GET[$name]) {
            $subname = $_GET[$name];
            $where['u.user_login'] = array("like", "%$subname%");
        }
        $count = M('gm_agentcharge')
            ->field("agc.*,u.user_login as subname,g.name as gamename")
            ->alias('agc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=agc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=agc.agent_id")
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $items = M('gm_agentcharge')
            ->field("agc.*,u.user_login as subname,g.name as gamename")
            ->alias('agc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=agc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=agc.agent_id")
            ->where($where)
            ->order("agc.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->select();
        $sums = M('gm_agentcharge')
            ->field("sum(agc.money) as total")
            ->alias('agc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=agc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=agc.agent_id")
            ->where($where)
            ->select();
//        print_r($sums);
        $this->assign("sums", $sums[0]['total']);
        $this->assign("agentname", $agent_name);
        $this->assign("orders", $items);
        $this->assign("formget", $_GET);
        $this->assign("Page", $page->show('Admin'));
        $this->assign("current_page", $page->GetCurrentPage());
        $this->display();
    }

    public function AgentForMmeber() {
        $agent_id = get_current_admin_id();
        $obj = new \Huosdk\Agent($agent_id);
        $subids = $obj->getMySubAgentsIds();
        $subids[] = $agent_id;
        $sub_txt = join(",", $subids);
        $where = array();
        $where['_string'] = "gc.admin_id IN ($sub_txt)";
        $hs_where_obj = new \Huosdk\Where();
        $hs_where_obj->time($where, "gc.create_time");
        $name = "agentname";
        if (isset($_GET[$name]) && $_GET[$name]) {
            $agentname = $_GET[$name];
            $where['u.user_login'] = array("like", "%$agentname%");
        }
        $name = "membername";
        if (isset($_GET[$name]) && $_GET[$name]) {
            $membername = $_GET[$name];
            $where['m.username'] = array("like", "%$membername%");
        }
        $name = "orderid";
        if (isset($_GET[$name]) && $_GET[$name]) {
            $orderid = $_GET[$name];
            $where['gc.order_id'] = array("eq", "$orderid");
        }
        $count = M('gm_charge')
            ->field("gc.*,u.user_login as agentname,g.name as gamename,m.username as membername")
            ->alias('gc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=gc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=gc.admin_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."members m ON m.id=gc.mem_id")
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $items = M('gm_charge')
            ->field("gc.*,u.user_login as agentname,g.name as gamename,m.username as membername")
            ->alias('gc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=gc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=gc.admin_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."members m ON m.id=gc.mem_id")
            ->where($where)
            ->order("gc.id desc")
            ->limit($page->firstRow.','.$page->listRows)
            ->select();
        $sums = M('gm_charge')
            ->field("sum(gc.money) as total")
            ->alias('gc')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=gc.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=gc.admin_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."members m ON m.id=gc.mem_id")
            ->where($where)
            ->select();
        $this->assign("sums", $sums[0]['total']);
//        foreach($items as $k => $item){
//            if($item['admin_id']==$agent_id){
//                $items[$k]['agent_type']='代理';
//            }else{
//                $items[$k]['agent_type']='下级代理';
//            }
//        }
        $this->assign("orders", $items);
        $this->assign("formget", $_GET);
        $this->assign("Page", $page->show('Admin'));
        $this->assign("current_page", $page->GetCurrentPage());
        $this->display();
    }
}


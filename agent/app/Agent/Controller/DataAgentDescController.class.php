<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2017/5/4
 * Time: 16:41
 */

namespace Agent\Controller;

use Common\Controller\AgentbaseController;

class DataAgentDescController extends AgentbaseController {
    function _initialize() {
        parent::_initialize();
        $this->mgRoleLog=M('mg_role_log');
        $this->loginLog=M('login_log');
    }

    public function getList($where_extra = array(), $start = 0, $limit = 0) {
        $agent_id = $this->agid;
        $user_ids = [$agent_id];
        $users = M('users')->field("id")->where('ownerid = ' . $agent_id)->select();
        if (is_array($users)) {
            foreach ($users as $user) {
                $user_ids[] = $user["id"];
            }
        }
        $where_extra['u.id'] = ['in', $user_ids];

        $model=M('mg_role');
        $records=$model
            ->field("mr.server as server_name,mr.role as role_name,mr.level as role_level,mr.create_time as register_time,
              g.name as game_name,m.username as mem_name,u.user_login as agent_name,g.id as game_id,mr.mem_id as mem_id
                 ")
            ->alias('mr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=mr.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."members m  ON mr.mem_id=m.id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u  ON m.agent_id=u.id")
            ->where($where_extra)
            ->order("mr.create_time desc")
            ->limit($start, $limit)
            ->select();
        $payway_data = $this->payway_txt();
        foreach ($records as $key => $value) {
            $mem_id = $value['mem_id'];
            $game_id = $value['game_id'];
            $role_name = $value['role_name'];
            $start_time = strtotime(date("Y-m-d", $value['register_time']));
            $end_time = $start_time + 3600 * 24;
            $mgRoleLogData = $this->mgRoleLog->where(
                "mem_id=$mem_id and app_id=$game_id and role_name='$role_name' and create_time <=$end_time and create_time >=$start_time"
            )->field("role_level")->order("create_time desc")->limit(1)->select();
            $records[$key]['day_role_level'] = $mgRoleLogData[0]['role_level'];
            $loginLogData = $this->loginLog->where("mem_id=$mem_id and app_id=$game_id")->field("deviceinfo,login_ip")
                                           ->order("login_time desc")->limit(1)->select();
            $records[$key]['register_ip'] = $loginLogData[0]['login_ip'];
            $records[$key]['device_info'] = $loginLogData[0]['deviceinfo'];
//            $records[$key]['payway_txt'] = $payway_data[$value['payway']];
            if (!$value['agent_name']) {
                $records[$key]['agent_name'] = "官方";
            }
        }

        return $records;
    }
    public function index(){

        $agent_id = $this->agid;
        $hs_agent_obj = new \Huosdk\Agent($agent_id);
        $ids = $hs_agent_obj->GetMeAndMySubAgentIDs();
        $where = array();

        $queryWay=I('queryWay');
        $keywords=trim(I('keywords'));
        $keywordsArray=[
            'tg'=>'推广员',
            'register'=>'注册账号',
            'gameRole'=>'游戏角色'
        ];
        if(!empty($keywords)){
            switch ($queryWay){
                case tg :
                    $where['u.user_login']=array('like',"%$keywords%");
                    break;
                case register :
                    $where['m.username']=array('like',"%$keywords%");
                    break;
                case gameRole :
                    $where['mr.role']=array('like',"%$keywords%");
                    break;
            }
        }
        $hs_where_obj = new \Huosdk\Where();
        $hs_where_obj->time_interval($where, "m.reg_time");

        if (isset($_GET['game']) && ($_GET['game']!=0)) {
            $v = $_GET['game'];
            $where['g.id'] = $v;
        }
        if (isset($_GET['server']) && preg_match("/[\x7f-\xff]/", $_GET['server'])) {
            $v = $_GET['server'];
            $where['mr.server'] = array('like',"%$v%");;
        }
        $count = $this->getCnt($where);

        $page = new \Think\Page($count, 20);
        $records = $this->getList($where, $page->firstRow, $page->listRows);
        /**
         ** 加入汇总
         ** 2016-12-17 16:12:27
         ** 严旭
         **/
        if ($_GET['submit'] == '导出数据') {
            $hs_ee_obj = new \Huosdk\Data\ExportExcel();
            $expTitle = "推广明细";
            $expCellName = array(
                array("agent_name", "渠道"),
                array("mem_name", "游戏账号"),
                array("device_info", "设备编号"),
                array("register_ip", "注册IP"),
                array("platform", "平台"),
                array("game_name", "游戏"),
                array("server_name", "分区"),
                array("role_name", "游戏角色"),
                array("register_time", "角色创建时间"),
            );
            $expTableData = $this->getList($where);
            foreach ($expTableData as $k => $v){
                $expTableData[$k]["register_time"] = date('Y-m-d H:i:s',$v["register_time"]);
                $expTableData[$k]["platform"]  = "九九乐动";
            }
            $hs_ee_obj->export($expTitle, $expCellName, $expTableData);
        }
        $this->assign("page", $page->show());
        $this->assign("keywordsArray", $keywordsArray);
        $this->assign("items", $records);
        $this->assign("formget", $_GET);
        $this->assign("n", $count);
        $this->display();
    }


    public function getCnt($where_extra = array()) {
        $agent_id = $this->agid;
        $user_ids = [$agent_id];
        $users = M('users')->field("id")->where('ownerid = ' . $agent_id)->select();
        if (is_array($users)) {
            foreach ($users as $user) {
                $user_ids[] = $user["id"];
            }
        }
        $where_extra['u.id'] = ['in', $user_ids];

        $_cnt = M("mg_role")
            ->field("mr.server as server_name,mr.role as role_name,mr.level as role_level,mr.create_time as register_time,
              g.name as game_name,m.username as mem_name,u.user_login as agent_name,g.id as game_id,mr.mem_id as mem_id
                 ")
            ->alias('mr')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=mr.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."members m  ON mr.mem_id=m.id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."users u  ON m.agent_id=u.id")
            ->where($where_extra)
            ->count();

        return $_cnt;
    }

}

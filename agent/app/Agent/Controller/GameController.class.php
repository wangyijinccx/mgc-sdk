<?php
/**
 * LoginController.class.php UTF-8
 * 登录接口
 *
 * @date    : 2016年7月21日下午8:27:36
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : H5 2.0
 */
namespace Agent\Controller;

use Common\Controller\AgentbaseController;

class GameController extends AgentbaseController {
    private $is_limit_two = true;

    function _initialize() {
        parent::_initialize();
        $this->is_limit_two = true;
    }

    public function apply_game() {
        in_case_notpass();
        $games = $this->get_apply_games();
        $choose_agents = $this->huoshu_agent->getMySubAgents();
        $this->assign("choose_agents", $choose_agents);
        $this->assign("games", $games);
        $this->assign("page_title", "申请游戏");
        $this->display();
    }

    /*获取渠道等级*/
    /**
     * @return mixed
     */
    private function get_apply_games() {
        $agent_id = $_SESSION['agent_id'];
        $where = "(g.promote_switch = 2) AND (g.is_delete = 2) AND (g.status = 2) "
                 ."AND  NOT EXISTS (select app_id from ".C('DB_PREFIX')
                 ."agent_game where g.id=app_id AND agent_id =$agent_id) "
                 ." ";
        if ($this->is_limit_two && $_SESSION['agent_id'] && '二级代理' == self::getAgentLevelById()) {
            /* 如果是二级渠道则只展示一级渠道申请了的游戏 */
            $_user_info = $this->user_info;
            if (isset($_user_info['ownerid']) && $_user_info['ownerid']) {
                $where .= " AND EXISTS(select app_id from ".C('DB_PREFIX')."agent_game where g.id=app_id AND agent_id="
                          .$_user_info['ownerid'].") ";
            }
        }
        $current_key = '';
        if (I('key') !== '') {
            $key = I('key');
            $where .= "AND ((g.name LIKE '%$key%') OR (g.id LIKE '%$key%')) ";
            $current_key = $key;
        }
        $model = M("game");
        $count = $model->alias('g')
                       ->join("LEFT JOIN ".C("DB_PREFIX")."game_version gv ON gv.app_id=g.id ")
                       ->join("LEFT JOIN ".C("DB_PREFIX")."game_rate gr ON gr.app_id=g.id ")
                       ->where($where)
                       ->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();// 分页显示输出
        $games = $model
            ->field(
                "g.id,g.name,IFNULL(gi.mobile_icon,g.icon) icon,g.update_time,g.classify,gv.size,gv.version,gr.agent_rate,gr.benefit_type"
            )
            ->alias('g')
            ->join("LEFT JOIN ".C("DB_PREFIX")."game_version gv ON gv.app_id=g.id ")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_info gi ON gi.app_id=g.game_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."game_rate gr ON gr.app_id=g.id ")
            ->where($where)
            ->order("g.id desc")
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        foreach ($games as $k => $v) {
            $games[$k]['size'] = round($v['size'] / (1024 * 1024));
            /* 解析ICON */
            if (strpos($v['icon'], "/") !== 0) {
                $games[$k]['icon'] = '/upload/image/'.$v['icon'];
            }
            if (empty($v['icon'])) {
                $games[$k]['icon'] = '/upload/ic_launcher.png';
            }
        }
        $this->assign("current_key", $current_key);
        $this->assign("total_count", $count);
        $this->assign("Page", $show);

        return $games;
    }

    /**
     * 判断是否为2级代理
     *
     * @return string
     */
    private function getAgentLevelById() {
        $agent_id = $_SESSION['agent_id'];
        $ut = M('users')->where(array("id" => $agent_id))->getField("user_type");
        $level = '';
        if ($ut == '6') {
            $level = '一级代理';
        } else if ($ut == '7') {
            $level = '二级代理';
        }

        return $level;
    }

    public function apply_game_post() {
        $list = I('list');
        if (empty($list)) {
            $this->ajaxReturn(array("error" => "1", "msg" => "参数错误"));
            exit;
        }
        if ($this->is_limit_two && $_SESSION['agent_id'] && '二级代理' == self::getAgentLevelById()) {
            /* 如果是二级渠道则只能申请对应上级渠道申请了的游戏 */
            $_user_info = $this->user_info;
            if (isset($_user_info['ownerid'])) {
                $where['app_id'] = ['in', $list];
                $where['agent_id'] = $_user_info['ownerid'];
                $where['is_delete'] = 2;
                $list_check = M('agent_game')->where($where)->select();
                if (empty($list_check)) {
                    $this->ajaxReturn(array("error" => "1", "msg" => "申请失败"));
                } else {
                    $_list_new = $this->newArrayColumn($list_check, 'app_id');
                    $this->huoshu_agent->addAgentGame($_list_new);
                    $this->ajaxReturn(array("error" => "0", "msg" => "申请成功"));
                }
            }
        }
        $this->huoshu_agent->addAgentGame($list);
        $this->ajaxReturn(array("error" => "0", "msg" => "申请成功"));
    }

    /**
     * 二维数组转一维
     *
     * @param array  $input
     * @param string $columnKey
     * @param string $indexKey
     *
     * @return array
     */
    public function newArrayColumn($input = [], $columnKey = '', $indexKey = null) {
        if (empty($input)) {
            return $input;
        }
        if (function_exists('array_column')) {
            return array_column($input, $columnKey, $indexKey);
        } else {
            $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
            $indexKeyIsNull = (is_null($indexKey)) ? true : false;
            $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
            $result = array();
            foreach ((array)$input as $key => $row) {
                if ($columnKeyIsNumber) {
                    $tmp = array_slice($row, $columnKey, 1);
                    $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
                } else {
                    $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
                }
                if (!$indexKeyIsNull) {
                    if ($indexKeyIsNumber) {
                        $key = array_slice($row, $indexKey, 1);
                        $key = (is_array($key) && !empty($key)) ? current($key) : null;
                        $key = is_null($key) ? 0 : $key;
                    } else {
                        $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                    }
                }
                $result[$key] = $tmp;
            }

            return $result;
        }
    }

    public function setDownVer() {
        $ag_id = I('ag_id/d');
        $ver = M('agent_game')->where(array("id" => $ag_id))->getField("ver");
        M('agent_game')->where(array("id" => $ag_id))->setField("down_ver", $ver);
    }

    public function mygames() {
        in_case_notpass();
        $agent_id = $_SESSION['agent_id'];

        //wyj -begin
        $userMap["id"] = $agent_id;
        $userResult = M("users")->where($userMap)->find();
        //wyj -end

        $where = "(ag.agent_id = $agent_id) AND (g.is_delete=2) AND (g.status=2) AND (g.promote_switch=2) ";
        $query_field = array();
        if (isset($_GET['gamename']) && $_GET['gamename']) {
            $gamename = $_GET['gamename'];
            $query_field['gamename'] = $gamename;
            $where .= "AND ((g.name like '%$gamename%') OR (g.id like '%$gamename%'))";
        }
        $model = M("agent_game");
        $count = $model
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->where($where)
            ->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();// 分页显示输出
        $games = $model
            ->field(
                "ag.*,g.name as gamename,g.classify,IFNULL(gi.mobile_icon,g.icon) icon,g.initial,agr.agent_rate,gv.packageurl downurl"
            )
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_info gi ON gi.app_id=g.game_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."agent_game_rate agr ON agr.ag_id=ag.id")
            ->where($where)
            ->order("ag.update_time desc")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        foreach ($games as $key => $value) {
            if (4 == $value['classify']) {
                $remote_app_fp = MOBILESITE."/mobile.php/Mobile/Game/sub?appid=".$value['app_id']."&agent=".$agent_id;
                $reg_app_fp = AGENTSITE."/agent.php/Front/Reg/ad_reg/name/".base64_encode($value['agentgame']);
                if (362657 == $value['app_id']) {
                    $reg_app_fp = AGENTSITE."/agent.php/Front/Reg/xy3d/name/".base64_encode($value['agentgame']);
                }else if (362671 == $value['app_id']) {
                    $reg_app_fp = AGENTSITE."/agent.php/Front/Reg/scws/name/".base64_encode($value['agentgame']);
                }
            } else {
//                if (true === strpos($value['url'],'sdkgame/')){
//                    $value['url'] = str_replace('sdkgame/','',$value['url']);
//                }
                $remote_app_fp = DOWNSITE.$value['url'];
                $reg_app_fp = AGENTSITE."/agent.php/Front/Reg/ad_reg/name/".base64_encode($value['agentgame']);
                // 六界凌霄定制化推广链接
                if (362659 == $value['app_id']) {
                    $reg_app_fp = AGENTSITE."/agent.php/Front/Reg/ljlx/name/".base64_encode($value['agentgame']);
                }else if (362670 == $value['app_id']) {
                    $reg_app_fp = AGENTSITE."/agent.php/Front/Reg/scws/name/".base64_encode($value['agentgame']);
                }
                if (362660 == $value['app_id']) {
                    $reg_app_fp = AGENTSITE."/agent.php/Front/Reg/xy3d/name/".base64_encode($value['agentgame']);
                }
                //               $remote_app_fp = DOWNSITE."?d.php?fn=".$value['url'];
            }
            /* 解析ICON */
            if (strpos($value['icon'], "/") !== 0 && strpos($value['icon'], "http") !== 0) {
                $games[$key]['icon'] = '/upload/image/'.$value['icon'];
            }
            $realfp_app = $remote_app_fp;
            $games[$key]['app_fp'] = $realfp_app;
            $games[$key]['reg_fp'] = $reg_app_fp;
        }
        \Huosdk\Data\FormatRecords::package_generate_status($games);
        $this->assign("query_field", $query_field);
        $this->assign("total_count", $count);
        $this->assign("Page", $show);
        $this->assign("games", $games);
        $this->assign("formget", $_GET);
        $this->assign("ownerid", $userResult['ownerid']);
        $this->assign("page_title", "我的游戏");
        $this->display();
    }

    public function _zqdata($games, $key, $value) {
        $savename = M("game")->where(array("id" => $value['app_id']))->getField("initial");
        $savename .= "_info";
        $zqdata_fp = SITE_PATH."access/upload/zqdata/".$savename.".zip";
        $zqdata_fp_rar = SITE_PATH."access/upload/zqdata/".$savename.".rar";
//            $zqdata_fp=iconv("utf-8","gb2312",$zqdata_fp);
        if (file_exists($zqdata_fp)) {
            $realfp = "/access/upload/zqdata/".$savename.".zip";
        } else if (file_exists($zqdata_fp_rar)) {
            $realfp = "/access/upload/zqdata/".$savename.".rar";
        } else {
            $realfp = '#';
        }
        $app_fp = SITE_PATH."download/sdkgame/".$value['initial']."/".$value['agentgame'].".apk";
        $games[$key]['zqdata_fp'] = $realfp;
    }

    public function gamesort() {
        $this->display();
    }

    public function gameinfo() {
        $this->ajaxReturn("hello");
    }

    public function cancelApply() {
        $model = M('agent_game');
        $model->where(array("id" => I('id')))->delete();
        $this->ajaxReturn(array("error" => "0", "msg" => "取消申请成功"));
    }

    public function needInfo() {
        $this->display();
    }

    public function get_rate_base($game_id) {
        $hs_benefit_obj = new \Huosdk\Benefit();
        $base = $hs_benefit_obj->get_app_default_agent_rate($game_id);

//        $base=M('game_rate')->where(array("id"=>$game_id))->getField("agent_rate");
        return $base;
    }

    public function get_appid_by_agent_game_id($agent_game_id) {
        return M('agent_game')->where(array("id" => $agent_game_id))->getField("app_id");
    }

    public function set_mem_rate() {
        $new_rate = I('rate');
        $agent_game_id = I('agent_game_id');
//        $app_id=$this->get_appid_by_agent_game_id($agent_game_id);
        if (!(is_numeric($new_rate) && $new_rate > 0 && $new_rate < 1)) {
            $this->ajaxReturn(array("error" => "1", "msg" => "折扣必须是介于0和1之间的数字"));
            exit;
        }
//        $base=$this->get_rate_base($app_id);
        $hs_benefit_obj = new \Huosdk\Benefit();
        $base = $hs_benefit_obj->get_agent_game_agent_rate($agent_game_id);
//        $base=M('agent_game_rate')->where(array("id"=>$agent_game_id))->getField("agent_rate");
        if ($new_rate < $base) {
            $this->ajaxReturn(array("error" => "1", "msg" => "折扣不能低于基线".$base));
            exit;
        }
        M('agent_game')->where(array("id" => $agent_game_id))->setField("mem_rate", $new_rate);
        $this->ajaxReturn(array("error" => "0", "msg" => "修改成功"));
    }

    public function generateApp() {
        in_case_notpass();
        $this->get_generateapp_items();
        $this->assign("page_title", "生成APP");
        $this->display();
    }

    public function get_generateapp_items() {
        $agent_id = $_SESSION['agent_id'];
        $where = array();
        $where["agent_id"] = $agent_id;
        $where["check_status"] = "2";
        $where["in_app"] = "1";
        $current_key = '';
        if (I('key') !== '') {
            $key = I('key');
            $where["_string"] = "((g.name LIKE '%$key%') OR (g.id LIKE '%$key%')) ";
            $current_key = $key;
        }
        $model = M("agent_game");
        $count = $model->alias('ag')
                       ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
                       ->join("LEFT JOIN ".C("DB_PREFIX")."game_version gv ON gv.app_id=g.id ")
                       ->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();// 分页显示输出
        $games = $model
            ->field("ag.*,g.name,gv.version,g.icon")
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id")
            ->where($where)
            ->order("update_time desc")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        /*
         * 如果图片不存在，用默认图片代替
         * 2016-08-10 11:08:21 严旭
         */
        foreach ($games as $key => $game) {
            $fp = SITE_PATH.'upload/'.$game['icon'];
            if (!file_exists($fp)) {
                $games["$key"]['icon'] = "/upload/default_app_icon.png";
            }
        }
        $this->assign("total_count", $count);
        $this->assign("Page", $show);
        $this->assign("games", $games);
    }

    public function inApp() {
        $this->get_inapp_items();
        $this->display();
    }

    public function get_inapp_items() {
        $agent_id = $_SESSION['agent_id'];
        $where = array();
        $where["agent_id"] = $agent_id;
        $where["check_status"] = "2";
        $where["in_app"] = "2";
        $current_key = '';
        if (I('key') !== '') {
            $key = I('key');
            $where["_string"] = "((g.name LIKE '%$key%') OR (g.id LIKE '%$key%')) ";
            $current_key = $key;
        }
        $model = M("agent_game");
        $count = $model->alias('ag')
                       ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
                       ->join("LEFT JOIN ".C("DB_PREFIX")."game_version gv ON gv.app_id=g.id ")
                       ->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();// 分页显示输出
        $games = $model
            ->field("ag.*,g.name,gv.version,g.icon")
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id")
            ->where($where)
            ->order("update_time desc")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        /*
         * 如果图片不存在，用默认图片代替
         * 2016-08-10 11:08:21 严旭
         */
        foreach ($games as $key => $game) {
            $fp = SITE_PATH.'upload/'.$game['icon'];
            if (!file_exists($fp)) {
                $games["$key"]['icon'] = "/upload/default_app_icon.png";
            }
        }
        $this->assign("total_count", $count);
        $this->assign("Page", $show);
        $this->assign("games", $games);
    }

    public function app_getcontent() {
        $agent_id = $_GET['agent_id'];
        $where = array();
        $model = M("agent_game");
        $where["agent_id"] = $agent_id;
        $where["check_status"] = "2";
        $where["in_app"] = "1";
        $count = $model->alias('ag')
                       ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
                       ->join("LEFT JOIN ".C("DB_PREFIX")."game_version gv ON gv.app_id=g.id ")
                       ->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();// 分页显示输出
        $games = $model
            ->field("ag.*,g.name,gv.version,g.icon")
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id")
            ->where($where)
            ->order("update_time desc")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        /*
        * 如果图片不存在，用默认图片代替
        * 2016-08-10 11:08:21 严旭
        */
        foreach ($games as $key => $game) {
            $fp = SITE_PATH.'upload/'.$game['icon'];
            if (!file_exists($fp)) {
                $games["$key"]['icon'] = "/upload/default_app_icon.png";
            }
        }
        $this->assign("total_count", $count);
        $this->assign("Page", $show);
        $this->assign("games", $games);
    }

    public function generateApp_post() {
        $list = array();
        $list = I('list');
        $agent_id = $_SESSION['agent_id'];
        $ids = join(",", $list);
        if ($list) {
            M('agent_game')->where("(agent_id=$agent_id) AND (id IN ($ids))")->setField("in_app", "2");
            $this->ajaxReturn(array("error" => "0", "msg" => "成功"));
        }
    }

    public function inApp_cancel_post() {
        $list = array();
        $list = I('list');
        $agent_id = $_SESSION['agent_id'];
        $ids = join(",", $list);
        if ($list) {
            M('agent_game')->where("(agent_id=$agent_id) AND (id IN ($ids))")->setField("in_app", "1");
            $this->ajaxReturn(array("error" => "0", "msg" => "成功"));
        }
    }

    public function toSub() {
        $sub_select = $this->huoshu_agent->mySubSelectList();
//        print_r($this->huoshu_agent->getSubleList(317));
//        exit;
        if (I('subid')) {
            $subid = I('subid');
            if ($subid > 0) {
                $sub_game_list = $this->huoshu_agent->getSubleList($subid);
                $this->assign("sub_game_list", $sub_game_list);
                $sub_select = $this->huoshu_agent->mySubSelectList($subid);
            }
        }
        $this->assign("subtxt", $sub_select);
        $this->display();
    }

//    public function app_getcontent3(){
//            $agent_id=$_GET['agent_id'];
//            
//            $where=array();
//            $model=M("agent_game");
//            
//            $where["agent_id"]=$agent_id;
//            $where["check_status"]="2";
//            $where["in_app"]="2";
//            
//            $count=$model->alias('ag')
//                ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id" )
//                ->join("LEFT JOIN ".C("DB_PREFIX")."game_version gv ON gv.app_id=g.id ")
//                ->where($where)->count();
//        
//            $Page= new \Think\Page($count,10);                            
//            $show = $Page->show();// 分页显示输出   
//
//            $games=$model
//                ->field("ag.*,g.name,gv.version,g.icon,gt.name as gametype")
//                ->alias('ag')
//                ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id" )
//                ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id" )
//                    ->join("LEFT JOIN ".C('DB_PREFIX')."game_type gt ON gt.id=g.type" )
//                ->where($where)                
//                ->order("update_time desc")
//                ->limit($Page->firstRow.','.$Page->listRows)
//                ->select();
//            
//            /*
//            * 如果图片不存在，用默认图片代替
//            * 2016-08-10 11:08:21 严旭
//            */
//           foreach($games as $key=>$game){
//               $fp=SITE_PATH.'upload/'.$game['icon'];
//               if(!file_exists($fp)){
//                   $games["$key"]['icon']="default_app_icon.png";
//               }
//           }
//            
//            $this->assign("total_count",$count);
//            $this->assign("Page", $show); 
//            $this->assign("games",$games);
//    }
//    public function app1(){
//        if(isset($_GET['agent_id']) && is_numeric($_GET['agent_id']) && ($_GET['agent_id']>=1)){
//            $this->app_getcontent();
//            $this->display("Game/app");
//        }
//        
//    }
//    
//    public function app2(){
//        if(isset($_GET['agent_id']) && is_numeric($_GET['agent_id']) && ($_GET['agent_id']>=1)){
//            $this->app_getcontent();
//            $this->display("Game/app_2");
//        }
//        
//    }
//    
//    public function app3(){
//        if(isset($_GET['agent_id']) && is_numeric($_GET['agent_id']) && ($_GET['agent_id']>=1)){
//            $this->app_getcontent3();
//            $this->display("Game/app_3");
//        }
//        
//    }
//    
//    public function app(){
//        if(isset($_GET['agent_id']) && is_numeric($_GET['agent_id']) && ($_GET['agent_id']>=1)){
//            $this->app_getcontent3();
//            $this->display("Game/app_4");
//        }
//        
//    }
    public function sub_mygames() {
        $sub_select = $this->huoshu_agent->mySubSelectList();
        $sub_apply_list = array();
//        print_r($this->huoshu_agent->getSubleList(317));
//        exit;
        if (I('subid')) {
            $subid = I('subid');
            if ($subid > 0) {
                $sub_apply_list = $this->huoshu_agent->getSubApplyList($subid);
                $sub_select = $this->huoshu_agent->mySubSelectList($subid);
            }
        }
        $this->assign("sub_game_list", $sub_apply_list);
        $this->assign("subtxt", $sub_select);
        $this->display();
    }

    public function pack() {
        $agid = I('id');
        $hs_package_obj = new \Huosdk\Package();
        $result = $hs_package_obj->pack($agid);
        if ($result['error'] == 0) {
            M('agent_game')->where(array("id" => $agid))->setField("status", 2);
        }
        $this->ajaxReturn($result);
    }

    public function get_game() {
        $spreadId = I('spread_id', 0);
        if ($spreadId == 0) {
            $this->ajaxReturn(['data' => [], 'type' => 'json']);
        }
        $gameData = $this->game_list($spreadId);
        $this->ajaxReturn(['data' => $gameData, 'type' => 'json']);
    }

    public function game_list($spreadId) {
        $agent_id = $this->agid;
        $user_ids = [$agent_id];
        if (1 == $agent_id) {
            $users = M('users')->where('ownerid', $agent_id)->select();
            if (is_array($users)) {
                $user_ids[] = $users["id"];
            }
        }
        $where = [];
        $where['ag.agent_id'] = ['in', $user_ids];
        $gameList = M('game')
            ->field("g.id as id, g.name as name")
            ->alias('g')
            ->join("LEFT JOIN ".C('DB_PREFIX')."agent_game ag ON ag.app_id = g.id")
            ->where($where)
            ->select();

        //把游戏的名字放到对应游戏的ID下
        return $gameList;
    }

    public function get_server() {
        $appId = I('game_id', 0);
        $serviceData = $this->server_list($appId);
        $this->ajaxReturn(['data' => $serviceData, 'type' => 'json']);
    }

    public function server_list($app_id) {
        $serviceList = M('gameServer')->where(['app_id' => $app_id, 'is_delete' => 2])->field("id,ser_name,ser_code")->select();
        return $serviceList;
    }

    private function get_apply_games_old() {
        $model = M("agent_game");
        $games = $model->order("id desc")->limit(10)->select();

        return $games;
    }

    /*
     * 开服列表
     * */
    private function id_in_my_games($id, $my_games) {
        foreach ($my_games as $mygame) {
            if ($mygame['app_id'] == $id) {
                return true;
            }
        }

        return false;
    }

    /*
     * 游戏列表
     * */
    private function get_my_games() {
        $model = M("agent_game");
        $games = $model
            ->field("ag.*,g.name as gamename")
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->where(array("agent_id" => $_SESSION['agent_id']))
            ->order("update_time desc")
            ->select();

        return $games;
    }

    private function get_apply_passed_games() {
        $agent_id = $_SESSION['agent_id'];
        $where = array();
        $where["agent_id"] = $agent_id;
        $where["check_status"] = "2";
        $model = M("agent_game");
        $games = $model
            ->field("ag.*,g.name,gv.version,g.icon")
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id")
            ->where($where)
            ->order("update_time desc")
            ->select();

        return $games;
    }
}

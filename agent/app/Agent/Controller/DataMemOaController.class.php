<?php
namespace Agent\Controller;

use Common\Controller\AgentbaseController;

class DataMemOaController extends AgentbaseController {
    function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $model = M("members");
//        $where=array("m.agent_id"=>$_SESSION['agent_id']);
        $subids = $this->huoshu_agent->getMySubAgentsIds();/* 子渠道 */
        array_push($subids, $this->agid);/* 自己 */
        $subids_txt = join(",", $subids);
        $where = array();
        //$where['agent_id']=$this->agid;
        if (isset($_GET['game_name']) && ($_GET['game_name'])) {
            $v = $_GET['game_name'];
            $where['g.name'] = array("like", "%$v%");
        }
        if (isset($_GET['game_name']) && ($_GET['game_name'])) {
            $v = $_GET['game_name'];
            $where['g.name'] = array("like", "%$v%");
        }
        $timewhere=array();
        if (isset($_GET['start_time']) && ($_GET['start_time'])) {
            $start_time = $_GET['start_time'];
            $timewhere[] = array("egt", $start_time);
        }
        if (isset($_GET['end_time']) && ($_GET['end_time'])) {
            $end_time = $_GET['end_time'];
            $timewhere[] = array("elt", $end_time);
        }
        if(!empty($timewhere)){
            switch(count($timewhere)){
                case 2:
                    $where['m.date']=array('between',array($start_time,$end_time));
                    break;
                case 1:
                    $where['m.date']=$timewhere[0];
                    break;
            }
        }
        if(!empty($where)){
            $map['_complex'] = $where;
        }
        //$subids 一级渠道和所有的二级渠道
        $map['agent_id'] =array('in',$subids);

        $count = $this->getCnt($map);

        $Page = new \Think\Page($count, 10);
        $items = $this->getList($map, $Page->firstRow, $Page->listRows);
        if ($_GET['submit'] == '导出数据') {
            $hs_ee_obj = new \Huosdk\Data\ExportExcel();
            $expTitle = "推广员业绩总览";
            $expCellName = array(
                array("date", "时间"),
                array("gamename", "游戏名称"),
                array("reg_cnt", "注册量"),
                array("reg_ip_cnt", "独立ip数"),
                array("standard_mem_cnt", "等级达标数"),
                array("order_cnt", "充值次数"),
                array("pay_mem_cnt", "充值人数"),
                array("sum_money_all", "充值总额"),
//                array("create_time", "添加时间"),
                array("update_time", "更新时间")
            );
            $expTableData = $this->getList($map);
            $hs_ee_obj->export($expTitle, $expCellName, $expTableData);
        }
        $this->assign("n", $count);
        $this->assign("page", $Page->show());
        $this->assign("members", $items);
        $this->assign("formget", $_GET);
        $this->assign("sum", $this->getSum($map));
        $this->assign("page_title", "推广员业绩总览");
        $this->display();


    }

    public function getList($where_extra = array(), $start = 0, $limit = 0) {
        $model = M("agent_oa");
        $members = $model
            ->field("m.*,g.name as gamename,SUM(m.reg_cnt) reg_cnt_all,SUM(m.reg_ip_cnt) reg_ip_cnt_all,SUM(m.standard_mem_cnt) standard_mem_cnt_all,SUM(m.order_cnt) order_cnt_all,SUM(m.pay_mem_cnt) pay_mem_cnt_all,SUM(m.sum_money) sum_money_all")
            ->alias('m')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=m.app_id")
            ->where($where_extra)
            ->group("date,app_id")
            ->order("m.date desc")
            ->limit($start, $limit)
            ->select();
        return $members;
    }

    public function getSum($where_extra = array()) {
        $model = M("agent_oa");
        $sum = $model
            ->alias('m')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=m.app_id")
            ->where($where_extra)
            ->sum("m.sum_money");
        return $sum;
    }



    public function getCnt($where_extra = array()) {
        $_cnt = M("agent_oa")
            ->alias('m')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=m.app_id")
            ->where($where_extra)
            ->group("date,app_id")
            ->select();
        return count($_cnt);
    }


}


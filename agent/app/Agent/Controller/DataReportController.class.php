<?php
namespace Agent\Controller;

use Common\Controller\AgentbaseController;

class DataReportController extends AgentbaseController {
    function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $where = array();
       // $where['dag.agent_id'] = $this->agid;
        $subids = $this->huoshu_agent->getMySubAgentsIds();/* 子渠道 */
        if(is_array($subids)){
            array_push($subids, $this->agid);/* 自己及所有子渠道 */
        }
        $hs_where_obj = new \Huosdk\Where();
        $hs_where_obj->get_simple_like($where, "game_name", "g.name");
        $hs_where_obj->time_eq($where, "unix_timestamp(dag.date)");
        $hs_where_obj->agent_name_with_official($where, "agent_name", "dag.agent_id", "u.user_nicename");
//        $all_items = $this->getList($where);

        if(!empty($where)){
            $map['_complex'] = $where;
        }
        //$subids 一级渠道和所有的二级渠道
        $map['agent_id'] =array('in',$subids);

        $count = $this->getCnt($map);
        $page = new \Think\Page($count, 10);
        $items = $this->getList($map, $page->firstRow, $page->listRows);
        if ($_GET['submit'] == '导出数据') {
            $hs_ee_obj = new \Huosdk\Data\ExportExcel();
            $expTitle = "统计报表";
            $expCellName = array(
                array("date", "时间"),
                array("agent_name", "渠道名称"),
                array("game_name", "游戏"),
                array("new_user_cnt", "新增用户"),
                array("active_user_cnt", "活跃用户"),
                array("charge_amount", "充值金额"),
                array("pay_user_cnt", "充值人数"),
                array("pay_rate", "付费率")
            );
            $expTableData = $this->getList($map);
            $hs_ee_obj->export($expTitle, $expCellName, $expTableData);
        }
        $this->assign("items", $items);
        $this->assign("page", $page->show());
        $this->assign("formget", $_GET);
        $this->assign("sum", $this->getSum($map));
        $this->display();
    }

    public function getList($where_extra = array(), $start = 0, $limit = 0) {
        $items = M('day_agentgame')
            ->field(
                "dag.*,g.name as game_name,u.user_nicename as agent_name,"
                ."dag.user_cnt as active_user_cnt,dag.sum_money as charge_amount,"
                ."dag.reg_cnt as new_user_cnt,dag.pay_user_cnt,"
                ."CONCAT(format((dag.pay_user_cnt/dag.user_cnt)*100,2),'%') as pay_rate"
            )
            ->alias("dag")
            ->where($where_extra)
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=dag.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=dag.agent_id")
            ->limit($start, $limit)
            ->order("dag.date desc,dag.agent_id")
            ->select();

        foreach ($items as $key => $value) {
            if (!$items[$key]['agent_name']) {
                $items[$key]['agent_name'] = "官方渠道";
            }
        }
        return $items;
    }

    public function getSum($where_extra = array()) {
        $sum = M('day_agentgame')
            ->alias("dag")
            ->where($where_extra)
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=dag.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=dag.agent_id")
            ->sum("dag.sum_money");
        return $sum;
    }

    public function getCnt($where_extra = array()) {
        $_cnt = M('day_agentgame')
            ->alias("dag")
            ->where($where_extra)
            ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=dag.app_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."users u ON u.id=dag.agent_id")
            ->count();
        return $_cnt;
    }
}


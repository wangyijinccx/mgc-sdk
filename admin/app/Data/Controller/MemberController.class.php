<?php
namespace Data\Controller;

use Common\Controller\AdminbaseController;

class MemberController extends AdminbaseController {
    protected $game_model, $members_model, $where, $ag_model;

    function _initialize() {
        parent::_initialize();
        $this->members_model = M("members");
        $this->game_model = D("Common/Game");
        $this->ag_model = M("agent_game");
    }

    function dataindex() {
        $this->_getuserdata();
        $this->display();
    }

    function loginindex() {
        $this->display();
    }

    /* 玩家数据 */
    function _getuserdata() {
        $start_time = trim(I('get.start_time'));
        $end_time = trim(I('get.end_time'));

        $mext_model = M('mem_ext');
        $where_ands = array("m.agent_id".$this->agentwhere);
        $fields = array(
            'username' => array(
                "field"    => "m.username",
                "operator" => "="
            ),
        );
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $get = trim($_POST[$param]);
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $get = trim($_GET[$param]);
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }
        $where = join(" and ", $where_ands);
        if (!empty($start_time)) {
            $start_unixtime = strtotime($start_time . ' 00:00:00');
            $where .= ' AND m.reg_time >= ' . $start_unixtime;
        }
        if (!empty($end_time)) {
            $end_unixtime = strtotime($end_time . ' 23:59:59');
            $where .= ' AND m.reg_time <= ' . $end_unixtime;
        }
        $count = $mext_model
            ->alias("me")
            ->join("left join ".C('DB_PREFIX')."members m ON me.mem_id = m.id")
            ->where($where)
            ->count();
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : $this->row;
        $page = $this->page($count, $rows);
        $field = "me.*, m.username, m.reg_time";
        $items = $mext_model
            ->alias("me")
            ->field($field)
            ->where($where)
            ->join("left join ".C('DB_PREFIX')."members m ON me.mem_id = m.id")
            ->order("m.id DESC")
            ->limit($page->firstRow.','.$page->listRows)
            ->select();
        
        $all_ltv = 0;
        $month_ltv = 0;
        $pay_model = M('pay');
        $pay_where = $where . ' AND p.status = 2';

        $moneys = $pay_model
            ->alias("p")
            ->field("sum(p.amount) as money")
            ->where($pay_where)
            ->join("left join ".C('DB_PREFIX')."members m ON p.mem_id = m.id")
            ->select();
        if (is_array($moneys) && 0 != $count) {
            $all_ltv = number_format($moneys[0]['money'] / $count, 2, '.', ''); 
        }

        $month_time = 30 * 24 * 60 * 60;
        $pay_where = $pay_where . ' AND p.create_time <= m.reg_time + ' . $month_time;
        $month_moneys = $pay_model
            ->alias('p')
            ->field("sum(p.amount) as money")
            ->where($pay_where)
            ->join("left join ".C('DB_PREFIX')."members m ON p.mem_id = m.id")
            ->select();
        if (is_array($month_moneys) && 0 != $count) {
            $month_ltv = number_format($month_moneys[0]['money'] / $count, 2, '.', ''); 
        }

        if ($_GET['submit'] == '导出数据') {
            $hs_ee_obj = new \Huosdk\Data\ExportExcel();
            $expTitle = "玩家数据";
            $expCellName = array(
                array("username", "玩家账号"),
                array("game_cnt", "游戏数量"),
                array("login_cnt", "登陆次数"),
                array("last_login_time", "最近登陆时间"),
                array("order_cnt", "充值次数"),
                array("sum_money", "充值总额"),
                array("last_money", "最近充值金额"),
                array("reg_time", "注册时间")
            );
            $export_items = $items = $mext_model
                ->alias("me")
                ->field($field)
                ->where($where)
                ->join("left join ".C('DB_PREFIX')."members m ON me.mem_id = m.id")
                ->order("m.id DESC")
                ->select();
            foreach ($export_items as $key => $value) {
                $export_items[$key]['reg_time'] = date("Y-m-d H:i:s", $export_items[$key]['reg_time']);
            }
            $expTableData = $export_items;
            $hs_ee_obj->export($expTitle, $expCellName, $expTableData);
        }
        $this->assign("all_ltv", $all_ltv);
        $this->assign("month_ltv", $month_ltv);
        $this->assign("count", $count);
        $this->assign("members", $items);
        $this->assign("formget", $_GET);
        $this->assign("Page", $page->show('Admin'));
        $this->assign("current_page", $page->GetCurrentPage());
    }
}

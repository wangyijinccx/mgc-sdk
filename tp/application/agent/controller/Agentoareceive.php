<?php
/**
 * System.php UTF-8
 * 系统公共操作
 *
 * @date    : 2016年12月3日上午11:00:44
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2016年12月3日上午11:00:44
 */
namespace app\agent\controller;
use app\common\controller\Base;
use think\Session;
use think\Db;
use huosdk\common\HuoSession;
use think\Config;
class Agentoareceive extends Base {
    private $agent_id;
    function _initialize() {
        parent::_initialize();
    }
    /**
     * @return $this
     */
    public function agentOaChange() {
        $_t_id = $this->request->param('t_id/d', 0); /* 触发主键id */
        $_t_name = $this->request->param('t_name'); /* 表名 */
        $_mem_id = $this->request->param('mem_id/d', 0); /* 玩家id */
        $_agent_id = $this->request->param('agent_id/d', 0); /* 推广员id */
        $_app_id = $this->request->param('app_id/d', 0); /* 游戏id*/
        $_do_time = $this->request->param('do_time'); /* 时间*/
        $_update_ip_cnt = $this->request->param('update_ip_cnt/d', 0); /* 更新ip数*/
        $_reg_cnt = $this->request->param('reg_cnt/d', 0); /* 注册数*/
        $_role_level = $this->request->param('role_level/d', 0); /* 用户等级*/
        $_order_cnt = $this->request->param('order_cnt/d', 0); /* 订单数*/
        $_check_order_cnt = $this->request->param('check_order_cnt/d', 0); /* 订单数*/
        $_update_pay_mem_cnt = $this->request->param('update_pay_mem_cnt/d', 0); /* 更新支付人数 */
        $_sum_money = $this->request->param('sum_money'); /* 充值总额*/
        $_real_amount = $this->request->param('real_amount'); /* 充值总额*/
        $_check_sum_money = $this->request->param('check_sum_money/d', 0); /* 检测更新充值总额*/
        $_update_s_mem_cnt = $this->request->param('update_s_mem_cnt/d', 0); /* 更新达标人数 */
        $doAgentOa = $this->request->param('doagentoa/d', 0);
        $agent_class = new \huosdk\agent\Agentoa($_agent_id);
        $all_data = $this->request->param();
        if (\huosdk\common\Commonfunc::isOaEnable() && 'pay' == $_t_name && 0 == $doAgentOa) {
            $all_data['doagentoa'] = 1;
            /* 参数不够要凑要查pay_ext数据库
            */
            $_field = "server_id,server_name,role_id,role_name,role_level,role,userua,agentgame,pay_ip,imei";
            $_map['pay_id'] = $all_data['id'];
            $_extData = Db::name('pay_ext')->field($_field)->where($_map)->find();
            $_all_data = array_merge($all_data, $_extData);
            $_ol_class = new \huosdk\oa\Oapay();
            $_ol_class->pay($_all_data);
            $_doinfo = $agent_class->request_pay_agent_oa($all_data);
        } else {
            if (!$_agent_id) {
                return hs_api_responce('500', '推广员id参数错误');
            }
            if (!$_app_id) {
                return hs_api_responce('501', '游戏id参数错误');
            }
            $_nowdotime = $_do_time ? $_do_time : time();
            $_agent_data = array(
                'mem_id'             => $_mem_id,
                't_id'               => $_t_id,
                't_name'             => $_t_name,
                'agent_id'           => $_agent_id,
                'app_id'             => $_app_id,
                'update_ip_cnt'      => $_update_ip_cnt,
                'reg_cnt'            => $_reg_cnt,
                'order_cnt'          => $_order_cnt,
                'check_order_cnt'    => $_check_order_cnt,
                'update_pay_mem_cnt' => $_update_pay_mem_cnt,
                'check_sum_money'    => $_check_sum_money,
                'update_s_mem_cnt'   => $_update_s_mem_cnt,
                'sum_money'          => $_sum_money,
                'role_level'         => $_role_level,
                'real_amount'        => $_real_amount,
                'date'               => $_nowdotime
            );
            $this->agent_id = $_agent_id;
            $_doinfo = $agent_class->change_agent_oa($_agent_data);
        }
        if (isset($_doinfo) && is_array($_doinfo) && !empty($_doinfo) && isset($_doinfo['code'])
            && '200' == $_doinfo['code']
        ) {
            return hs_api_responce('200', '处理成功', $_doinfo);
        } else {
            /* > 300 就会throw*/
            return hs_api_responce('500', '处理失败');
        }
    }
}
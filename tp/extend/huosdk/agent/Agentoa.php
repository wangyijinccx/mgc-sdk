<?php
/**
 * Agentgame.php UTF-8
 * 渠道游戏处理
 *
 * @date    : 2017/1/16 21:14
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 */
namespace huosdk\agent;

use think\Log;
use think\Db;

class Agentoa extends Agent {
    private $agent_id;
    private $today;
    private $app_id;

    /**
     * 自定义错误处理
     *
     * @param msg 输出的文件
     */
    private function _error($msg, $level = 'error') {
        $_info = 'agent\Agentgame Error:'.$msg;
        Log::record($_info, 'error');
    }

    /**
     * 构造函数
     *
     * @param $agentid   INT 代理ID
     * @param $agentgame string 代理游戏名称
     */
    public function __construct($agent_id = 0, $agentgame = '') {
        if (empty($agent_id)) {
            $this->setAgentidfromAg($agentgame);
        } else {
            $this->agent_id = $agent_id;
        }
        $this->today = date('Y-m-d');
    }

    /**
     * 设置agentid
     *
     * @param $agentid INT 代理ID
     */
    public function setAgentid($agentid) {
        $this->agent_id = $agentid;
    }

    /**
     * 通过agentgame设置agentid
     *
     * @param $agentid INT 代理ID
     */
    public function setAgentidfromAg($agentgame) {
        if (empty($agentgame) || 'default' == $agentgame) {
            $this->agent_id = 0;

            return;
        }
        $_map['agentgame'] = $agentgame;
        $_agent_id = DB::name('agent_game')->where($_map)->value('agent_id');
        if (empty($_agent_id)) {
            $this->agent_id = 0;

            return;
        }
        $this->agent_id = $_agent_id;
    }

    /**
     * 获取agentid
     */
    public function getAgentid() {
        return $this->agent_id;
    }

    /**
     * 注册成功，支付回调，上传角色后调起的异步处理
     * 有传值则为加一处理
     * $reg_data 不能为空
     * 至少要有推广员的agent_id和游戏的app_id
     *  date为处理时间 可以为数字和时间格式
     *
     * 后续需要考虑并发
     * 查询后统计就要传参数
     * 这接口每天每个推广员的每个游戏运行一次就可以了
     *
     * @param array $agent_data
     *
     * @return array
     *
     */
    public function change_agent_oa($agent_data = array()) {
        $_check_data = $this->check_agent_oa_exent($agent_data);
        if (empty($_check_data)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 503,
                'status_desc' => '参数未传或格式错误',
                'func'        => 'change_agent_oa',
                'info'        => array()
            );
        }
        if (!isset($_check_data['info']) || empty($_check_data['info'])) {
            return $_check_data;
        }
        //Log::write($_check_data, 'error');
        if (isset($_check_data['code']) && 200 == $_check_data['code']) {
            /* 需走更新 注册完进行判断是否更新独立ip数 注册人数肯定要更新 支付完判断是否更新支付人数 次数和金额肯定要更新  */
            return $this->update_change_agent_oa($_check_data['info']);
        } else {
            return $this->initialize_agent_oa($_check_data['info']);
        }
    }

    /**
     * 更新推广员业绩数据
     *
     * @param array $update_data
     *
     * @return array
     */
    public function update_change_agent_oa($update_data = array()) {
        if (empty($update_data) || !isset($update_data['id']) || !$update_data['id']
            || !is_numeric(
                $update_data['id']
            )
        ) {
            return array(
                'status'      => 'FAIL',
                'code'        => 403,
                'status_desc' => '参数未传或格式错误',
                'func'        => 'update_change_agent_oa',
                'data'        => $update_data,
                'info'        => array()
            );
        }
        $_oa_map = array();
        $_oa_map['id'] = $update_data['id'];
        $_update_data = array();
        $_oa_info = Db::name('agent_oa')->where($_oa_map)->field('agent_id,date,app_id,is_standard')->find();
        if (empty($_oa_info)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 408,
                'status_desc' => '操作失败未找到信息',
                'func'        => 'update_change_agent_oa',
                'info'        => array()
            );
        }
        if (isset($update_data['reg_cnt']) && $update_data['reg_cnt'] && is_numeric($update_data['reg_cnt'])) {
            $_update_data['reg_cnt'] = ['exp',
                                        'reg_cnt'.($update_data['reg_cnt'] > 0 ? '+' : '').$update_data['reg_cnt']];
        }
        if (isset($update_data['update_ip_cnt']) && $update_data['update_ip_cnt']) {
            /* $_update_data['reg_ip_cnt']= ['exp','reg_ip_cnt'.($update_data['reg_ip_cnt']>0?'+':'').$update_data['reg_ip_cnt']]; */
            $_update_data['reg_ip_cnt'] = $this->get_agent_distinct_reg_ip_cnt($_oa_info);
        }
        if (isset($update_data['update_s_mem_cnt']) && $update_data['update_s_mem_cnt']) {
            $_update_data['standard_mem_cnt'] = $this->get_agent_standard_mem_cnt($_oa_info);
        }
        if (isset($update_data['order_cnt']) && $update_data['order_cnt'] && is_numeric($update_data['order_cnt'])) {
            $_update_data['order_cnt'] = ['exp', 'order_cnt'.($update_data['order_cnt'] > 0 ? '+' : '')
                                                 .$update_data['order_cnt']];
        }
        if (isset($update_data['check_order_cnt']) && $update_data['check_order_cnt']
            && is_numeric(
                $update_data['check_order_cnt']
            )
        ) {
            $_update_data['order_cnt'] = $this->get_agent_order_cnt($_oa_info);
        }
        if (isset($update_data['update_pay_mem_cnt']) && $update_data['update_pay_mem_cnt']) {
            $_update_data['pay_mem_cnt'] = $this->get_agent_pay_mem_cnt($_oa_info);
        }
        if ((isset($update_data['sum_money']) && $update_data['sum_money'] && is_numeric($update_data['sum_money']))
            || (isset($update_data['check_sum_money']) && $update_data['check_sum_money']
                && is_numeric(
                    $update_data['check_sum_money']
                ))
        ) {
            /* sum_money 如果累加会造成严重的误差 */
            $_update_data['sum_money'] = $this->get_agent_sum_money($_oa_info);
        }
        $_update_data['update_time'] = time();
        $update_do = Db::name('agent_oa')->where($_oa_map)->update($_update_data);
        if ($update_do) {
            define('NOT_STANDARD', 1);
            if (NOT_STANDARD == $_oa_info['is_standard']) {/* 当还不及格的时候再检测是否及格 */
                $check_is_standard = $this->check_is_standard($_oa_map);
                if (NOT_STANDARD != $check_is_standard) {
                    Db::name('agent_oa')->where($_oa_map)->setField('is_standard', $check_is_standard);
                }
            }

            return array(
                'status'      => 'OK',
                'code'        => 200,
                'status_desc' => '操作成功',
                'func'        => 'update_change_agent_oa',
                'info'        => array(
                    'update_do' => $update_do
                )
            );
        } else {
            return array(
                'status'      => 'FAIL',
                'code'        => 409,
                'status_desc' => '操作失败',
                'func'        => 'update_change_agent_oa',
                'info'        => array(
                    'update_do' => $update_do
                )
            );
        }
    }

    /**
     * 检测当天数据记录是否已有记录 code 200 已有 201 未有
     *
     * @param array $agent_data
     *
     * @return array
     */
    public function check_agent_oa_exent($agent_data = array()) {
        if (empty($agent_data)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 503,
                'status_desc' => '参数未传或格式错误',
                'func'        => 'check_agent_oa_exent',
                'info'        => array()
            );
        }
        $_agent_id = ($agent_data['agent_id'] && is_numeric($agent_data['agent_id'])) ? $agent_data['agent_id']
            : $this->agent_id;
        if (!$_agent_id || !is_numeric($_agent_id)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 504,
                'status_desc' => '参数agent_id未传或格式错误',
                'func'        => 'check_agent_oa_exent',
                'info'        => array()
            );
        }
        if (!$agent_data['app_id'] || !is_numeric($agent_data['app_id'])) {
            return array(
                'status'      => 'FAIL',
                'code'        => 505,
                'status_desc' => '参数app_id未传或格式错误',
                'func'        => 'check_agent_oa_exent',
                'info'        => array()
            );
        }
        $_reg_today = $agent_data['date'] ? (is_numeric($agent_data['date']) ? date('Y-m-d', $agent_data['date'])
            : $agent_data['date']) : $this->today;
        $_oa_map = array(
            'agent_id' => $_agent_id,
            'app_id'   => $agent_data['app_id'],
            'date'     => $_reg_today
        );
        $this->app_id = $agent_data['app_id'];
        $this->today = $_reg_today;
        $_agent_oa_data = DB::name('agent_oa')->where($_oa_map)->field('id,date,app_id,agent_id')->find();
        if (!empty($_agent_oa_data)) {
            // Log::write($agent_data, 'error');
            // Log::write($_agent_oa_data, 'error');
            $_info = array_merge($agent_data, $_agent_oa_data);
            //Log::write($_info, 'error');

            return array(
                'status'      => 'OK',
                'code'        => 200,
                'status_desc' => '已有记录',
                'func'        => 'check_agent_oa_exent',
                'info'        => $_info
            );
        } else {
            $_info = array_merge($agent_data, $_oa_map);

            return array(
                'status'      => 'OK',
                'code'        => 201,
                'status_desc' => '未找到该记录',
                'func'        => 'check_agent_oa_exent',
                'info'        => $_info
            );
        }
    }

    /**
     * 初始化某天某个推广员的某个游戏记录
     *
     * @param array $agent_data
     *
     * @return array
     */
    public function initialize_agent_oa($agent_data = array()) {
        $_check_data = $this->check_agent_oa_exent($agent_data);
        if (empty($_check_data)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 503,
                'status_desc' => '参数未传或格式错误',
                'func'        => 'initialize_agent_oa',
                'info'        => array()
            );
        }
        if (!isset($_check_data['info']) || empty($_check_data['info'])) {
            return $_check_data;
        } else {
            $_agent_oa_data = $_check_data['info'];
        }
        if (isset($_check_data['code']) && 200 == $_check_data['code']) {
            /* 走更新流程 */
            $_update_agent_oa = $this->update_agent_oa($_agent_oa_data);
            if (is_array($_update_agent_oa) && !empty($_update_agent_oa) && isset($_update_agent_oa['status'])
                && 'OK' == $_update_agent_oa['status']
            ) {
                return $_update_agent_oa;
            } else {
                return $_update_agent_oa;
            }
        } else {
            /* 还没有记录 */
            $_save_info = $this->save_agent_oa($_agent_oa_data);
            if (is_array($_save_info) && !empty($_save_info) && isset($_save_info['status'])
                && 'OK' == $_save_info['status']
            ) {
                /* 处理成功 */
                return $_save_info;
            } else {
                return $_save_info;
            }
        }
    }

    /**
     * 直接统计更新当天推广员某游戏数据
     *
     * @param array $agent_oa_data
     *
     * @return array
     */
    public function update_agent_oa($agent_oa_data = array()) {
        if (!is_array($agent_oa_data) || empty($agent_oa_data)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 503,
                'status_desc' => '参数为空',
                'func'        => 'update_agent_oa',
                'info'        => array()
            );
        }
        if (!isset($agent_oa_data['id']) || !is_numeric($agent_oa_data['id'])) {
            return array(
                'status'      => 'FAIL',
                'code'        => 504,
                'status_desc' => '参数为空',
                'func'        => 'update_agent_oa',
                'info'        => array()
            );
        }
        $_oa_map = array();
        $_oa_map['id'] = $agent_oa_data['id'];
        $_oa_info = Db::name('agent_oa')->where($_oa_map)->field('agent_id,app_id,date')->find();
        if (!is_array($_oa_info) || empty($_oa_info)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 404,
                'status_desc' => '未找到相关记录',
                'func'        => 'update_agent_oa',
                'info'        => array()
            );
        }
        $_update_data = array();
        $_update_data['reg_cnt'] = $this->get_agent_reg_cnt($_oa_info);
        $_update_data['reg_ip_cnt'] = $this->get_agent_distinct_reg_ip_cnt($_oa_info);
        $_update_data['order_cnt'] = $this->get_agent_order_cnt($_oa_info);
        $_update_data['pay_mem_cnt'] = $this->get_agent_pay_mem_cnt($_oa_info);
        $_update_data['sum_money'] = $this->get_agent_sum_money($_oa_info);
        $_update_data['standard_mem_cnt'] = $this->get_agent_standard_mem_cnt($_oa_info);
        $_update_data['is_standard'] = $this->check_is_standard($_update_data);
        $_update_data['update_time'] = time();
        $update_do = Db::name('agent_oa')->where($_oa_map)->update($_update_data);
        if ($update_do) {
            return array(
                'status'      => 'OK',
                'code'        => 200,
                'status_desc' => '更新成功',
                'func'        => 'update_agent_oa',
                'info'        => array(
                    'update_do' => $update_do,
                    'id'        => $agent_oa_data['id']
                )
            );
        } else {
            return array(
                'status'      => 'FAIL',
                'code'        => 500,
                'status_desc' => '更新失败',
                'func'        => 'update_agent_oa',
                'info'        => array()
            );
        }
    }

    /**
     * 统计初始化推广员某游戏当天数据
     *
     * @param array $agent_info
     *
     * @return array
     */
    public function save_agent_oa($agent_info = array()) {
        if (!is_array($agent_info) || empty($agent_info)) {
            return array(
                'status'      => 'FAIL',
                'code'        => 503,
                'status_desc' => '参数为空或未传',
                'func'        => 'update_agent_oa',
                'info'        => array()
            );
        }
        if (!isset($agent_info['agent_id']) || !$agent_info['agent_id'] || !is_numeric($agent_info['agent_id'])) {
            return array(
                'status'      => 'FAIL',
                'code'        => 504,
                'status_desc' => '参数agent_id未传或不是正确的数字类型',
                'func'        => 'update_agent_oa',
                'info'        => array()
            );
        }
        if (!isset($agent_info['app_id']) || !$agent_info['app_id'] || !is_numeric($agent_info['app_id'])) {
            return array(
                'status'      => 'FAIL',
                'code'        => 505,
                'status_desc' => '参数app_id未传或不是正确的数字类型',
                'func'        => 'update_agent_oa',
                'info'        => array()
            );
        }
        $_oa_data = array();
        $_oa_data['agent_id'] = $agent_info['agent_id'];
        $_oa_data['app_id'] = $agent_info['app_id'];
        $_oa_data['date'] = isset($agent_info['date']) ? $agent_info['date'] : $this->taday;
        $_oa_data['reg_cnt'] = $this->get_agent_reg_cnt($agent_info);
        $_oa_data['reg_ip_cnt'] = $this->get_agent_distinct_reg_ip_cnt($agent_info);
        $_oa_data['order_cnt'] = $this->get_agent_order_cnt($agent_info);
        $_oa_data['pay_mem_cnt'] = $this->get_agent_pay_mem_cnt($agent_info);
        $_oa_data['sum_money'] = $this->get_agent_sum_money($agent_info);
        $_oa_data['standard_mem_cnt'] = $this->get_agent_standard_mem_cnt($agent_info);
        $_oa_data['is_standard'] = $this->check_is_standard($_oa_data);
        $_oa_data['create_time'] = time();
        $_oa_data['update_time'] = 0;
        $agent_oa_id = Db::name('agent_oa')->insertGetId($_oa_data);
        if ($agent_oa_id) {
            return array(
                'status'      => 'OK',
                'code'        => 200,
                'status_desc' => '添加处理成功',
                'func'        => 'save_agent_oa',
                'info'        => array(
                    'agent_oa_id' => $agent_oa_id
                )
            );
        } else {
            return array(
                'status'      => 'FAIL',
                'code'        => 400,
                'status_desc' => '添加处理失败',
                'func'        => 'save_agent_oa',
                'info'        => array(
                    'agent_oa_id' => $agent_oa_id
                )
            );
        }
    }

    /**
     * 获取达标数量Db::query("select * from think_user where status=1");
     *
     * @param array $agent_info
     *
     * @return int
     */
    public function get_agent_standard_mem_cnt(
        $agent_info = array()
    ) {
        /* 先找出当天注册的用户 */
        $_agent_info_check = $this->check_agent_info($agent_info);
        if (!is_array($_agent_info_check) || empty($_agent_info_check)) {
            return 0;
        }
        $_standard_level = $this->get_standard_level($agent_info);
        if ($_standard_level == 0) {
            return 0;
        }
        $DB_PREFIX = \think\Config::get('database.prefix');
        $countsql = "select count(DISTINCT(r.mem_id)) from";
        $countsql .= " ".$DB_PREFIX."mg_role_log as r left JOIN ".$DB_PREFIX."members as m on r.mem_id=m.id";
        $countsql .= " where r.role_level>='".$_standard_level."'";
        $countsql .= " and m.agent_id=".$_agent_info_check['agent_id'];
        $countsql .= " and m.app_id=".$_agent_info_check['app_id'];
        /* strtotime($has_check_where['date']), strtotime($has_check_where['date']." +1 day") */
        $countsql .= " and r.create_time>='".strtotime($_agent_info_check['date'])."'";
        $countsql .= " and r.create_time<'".strtotime($_agent_info_check['date']." +1 day")."'";
        $countsql .= " and m.reg_time>='".strtotime($_agent_info_check['date'])."'";
        $countsql .= " and m.reg_time<'".strtotime($_agent_info_check['date']." +1 day")."'";
        \think\Log::write($countsql, 'debug');
        $_standard_query = DB::query($countsql);
        \think\Log::write($_standard_query, 'debug');
        if (!empty($_standard_query)) {
            if (isset($_standard_query[0]) && !empty($_standard_query[0])) {
                return isset($_standard_query[0]['count(DISTINCT(r.mem_id))'])
                    ? $_standard_query[0]['count(DISTINCT(r.mem_id))'] : 0;
            }
        }

        return 0;
    }

    /**
     * 获取游戏的达标等级设置
     *
     * @param array $agent_info
     *
     * @return int|mixed
     */
    public function get_standard_level($agent_info = array()) {
        if ($agent_info['app_id'] && is_numeric($agent_info['app_id'])) {
            $_game_map = array();
            $_game_map['id'] = $agent_info['app_id'];
            $_standard_level = DB::name('game')->where($_game_map)->value('standard_level');

            return $_standard_level ? $_standard_level : 0;
        } else {
            return 0;
        }
    }

    /**
     * 判断是否达标
     *
     * @param array $oa_data
     *
     * @return int
     */
    public function check_is_standard($oa_data = array()) {
        if (!empty($oa_data) && isset($oa_data['id']) && $oa_data['id']) {
            $_oamap = array();
            $_oamap['id'] = $oa_data['id'];
            $_agent_info_check = DB::name('agent_oa')->where($_oamap)->field('agent_id,app_id,date')->find();
            $_agent_info_check['standard_mem_cnt'] = $this->get_agent_standard_mem_cnt($_agent_info_check);
            $_agent_info_check['sum_money'] = $this->get_agent_sum_money($_agent_info_check);
        } else {
            $_agent_info_check = $this->check_agent_info($oa_data);
        }
        if (!is_array($_agent_info_check) || empty($_agent_info_check)) {
            return 1;
        }
        if (!isset($_agent_info_check['standard_mem_cnt']) || !$_agent_info_check['standard_mem_cnt']) {
            return 1;/* 没有设置达标人数则所有人都不合格 */
        }
        $_get_standard_mem_cnt = $this->get_standard_mem_cnt($_agent_info_check);
        if ($_agent_info_check['standard_mem_cnt'] > $_get_standard_mem_cnt) {
            /* 达标人数大于推广人数 肯定合格 */
            return 2;
        }
        /* 达标人数小于或等于推广人数 是要看消费奖金（ 角色关联玩家每日消费总额*积分等级对应提成） */
        if (0.00 == $_agent_info_check['sum_money'] || !$_agent_info_check['sum_money']) {
            return 1;/* 玩家消费额不大于零肯定不及格 */
        }
        if ($_get_standard_mem_cnt == $_agent_info_check['standard_mem_cnt']) {
            /* 达标人数等于设置达标数时 只要有消费 就及格 */
            return 2;
        }
        /* 消费额达到某个点后才能 算及格 推广一个达标人多少钱 5？写死吗 最底2个点 和 最高5个点怎么配置 都是跟游戏有关的吧  */
        $chae = ($_get_standard_mem_cnt - $_agent_info_check['standard_mem_cnt']) * 500;
        if ($_agent_info_check['sum_money'] * 2 > $chae) {
            return 2;
        }
        if ($_agent_info_check['sum_money'] * 5 < $chae) {
            return 1;
        }

        return 1;
    }

    /**
     * 获取达标人数设置
     *
     * @param array $agent_info
     *
     * @return int|mixed
     */
    public function get_standard_mem_cnt($agent_info = array()) {
        if ($agent_info['app_id'] && is_numeric($agent_info['app_id'])) {
            $_game_map = array();
            $_game_map['id'] = $agent_info['app_id'];
            $standard_mem_cnt = DB::name('game')->where($_game_map)->value('standard_mem_cnt');
            $standard_mem_cnt = $standard_mem_cnt ? $standard_mem_cnt : 0;

            return $standard_mem_cnt;
        } else {
            return 0;
        }
    }

    /**
     * 更新推广员当天的注册独立ip数
     *
     * @param array $member_info
     */
    public function get_agent_distinct_reg_ip_cnt($agent_info = array()) {
        $_reg_ip_map = $this->get_agent_daily_reg_where_arr($agent_info);
        if (!is_array($_reg_ip_map) || empty($_reg_ip_map)) {
            return 0;
        }
        /* ->whereTime('reg_time', 'd') 会有问题 避免如2016年多一秒少一秒等情况 造成一天并不只24*3600 86400 */
        $_reg_ip_cnt = DB::name('members')->where($_reg_ip_map)->count('DISTINCT(`regist_ip`)');

        return $_reg_ip_cnt ? $_reg_ip_cnt : 0;
    }

    /**
     * 获取推广员下的当天注册量
     *
     * @param array $member_info
     */
    public function get_agent_reg_cnt($agent_info = array()) {
        $_reg_cnt_map = $this->get_agent_daily_reg_where_arr($agent_info);
        if (!is_array($_reg_cnt_map) || empty($_reg_cnt_map)) {
            return 0;
        }
        $_reg_cnt = DB::name('members')->where($_reg_cnt_map)->count();

        return $_reg_cnt ? $_reg_cnt : 0;
    }

    /**
     * 获取订单数
     *
     * @param array $pay_data
     */
    public function get_agent_order_cnt($agent_info = array()) {
        $_order_cnt_map = $this->get_agent_daily_order_where_arr($agent_info);
        if (!is_array($_order_cnt_map) || empty($_order_cnt_map)) {
            return 0;
        }
        $_order_cnt = DB::name('pay')->where($_order_cnt_map)->count();

        return $_order_cnt ? $_order_cnt : 0;
    }

    /**
     * 获取充值人数
     *
     * @param array $agent_info
     *
     * @return int
     */
    public function get_agent_pay_mem_cnt($agent_info = array()) {
        $_pay_mem_cnt_map = $this->get_agent_daily_order_where_arr($agent_info);
        if (!is_array($_pay_mem_cnt_map) || empty($_pay_mem_cnt_map)) {
            return 0;
        }
        $_pay_mem_cnt = DB::name('pay')->where($_pay_mem_cnt_map)->count('DISTINCT(`mem_id`)');

        return $_pay_mem_cnt ? $_pay_mem_cnt : 0;
    }

    /**
     * 获取 推广员下的玩家的某一游戏的 充值总额
     *
     * @param array $agent_info
     *
     * @return float|int
     */
    public function get_agent_sum_money($agent_info = array()) {
        $_sum_money_map = $this->get_agent_daily_order_where_arr($agent_info);
        if (!is_array($_sum_money_map) || empty($_sum_money_map)) {
            return 0;
        }/* 直接用amount */
        $_sum_money = DB::name('pay')->where($_sum_money_map)->sum('amount');

        return $_sum_money ? $_sum_money : 0.00;
    }

    /**
     * 获取支付相关的sql筛选
     *
     * @param array $agent_info
     *
     * @return array
     */
    public function get_agent_daily_order_where_arr($agent_info = array()) {
        $has_check_agent_info = $this->check_agent_info($agent_info);
        if (empty($has_check_agent_info)) {
            return array();
        }
        $_daily_order_map = [
            'agent_id'    => ['eq', $has_check_agent_info['agent_id']],
            'app_id'      => ['eq', $has_check_agent_info['app_id']],
            'status'      => ['eq', PAYSTATUS_SUCCESS],
            'update_time' => ['between', [strtotime($has_check_agent_info['date']),
                                          strtotime($has_check_agent_info['date']." +1 day")]],
        ];

        return $_daily_order_map;
    }

    /**
     * 正确获取值 若不存在则使用默认
     *
     * @copy form member.php
     *
     * @param        $data
     * @param        $key
     * @param string $default
     *
     * @return string
     */
    public function getVal($data, $key, $default = '') {
        if (empty($key) || empty($data) || !isset($data[$key])) {
            return $default;
        }

        return $data[$key];
    }

    /**
     * 获取每天统计的条件
     *
     * @param array $where
     *
     * @return array
     */
    public function get_agent_daily_reg_where_arr($where = array()) {
        $has_check_where = $this->check_agent_info($where);
        if (empty($has_check_where)) {
            return array();
        }
        $_daily_map = [
            'agent_id' => ['eq', $has_check_where['agent_id']],
            'app_id'   => ['eq', $has_check_where['app_id']],
            'reg_time' => ['between',
                           [strtotime($has_check_where['date']), strtotime($has_check_where['date']." +1 day")]],
        ];

        return $_daily_map;
    }

    /**
     * 获取推广员下的所有游戏id
     *
     * @param int $agent_id
     */
    public function get_agent_app_id_list($agent_id = 0) {
        if (!$agent_id || !is_numeric($agent_id)) {
            if ($this->agent_id) {
                $agent_id = $this->agent_id;
            } else {
                return array();
            }
        }
        $_agent_game_map = array();
        $_agent_game_map['agent_id'] = $agent_id;
        $_agent_game_map['status'] = 2;
        $_agent_game_map['is_delete'] = 2;
        $app_id_list = DB::name('agent_game')->where($_agent_game_map)->field('app_id')->select();

        return $app_id_list;
    }

    /**
     * 检测 $agent_info 数据 用于生成条件语句
     *
     * @param array $agent_info
     *
     * @return array
     */
    public function check_agent_info($agent_info = array()) {
        $_today = isset($agent_info['date']) ? (is_numeric($agent_info['date']) ? date('Y-m-d', $agent_info['date'])
            : $agent_info['date']) : $this->today;
        if (!$_today) {
            $_today = date('Y-m-d');
        }
        $_app_id = isset($agent_info['app_id']) ? $agent_info['app_id'] : $this->app_id;
        if (!$_app_id || !is_numeric($_app_id)) {
            return array();
        }
        $_agent_id = isset($agent_info['agent_id']) ? $agent_info['agent_id'] : $this->agent_id;
        if (!$_agent_id || !is_numeric($_agent_id)) {
            return array();
        }
        $_agent_info = array();
        $_agent_info['date'] = $_today;
        $_agent_info['app_id'] = $_app_id;
        $_agent_info['agent_id'] = $_agent_id;

        return $_agent_info;
    }

    /**
     * 异步更新推广员业绩表
     *
     * @param array $mem_data
     *
     * @return \huosdk\request\请求结果
     */
    public function request_agent_oa($mem_data = array()) {
        $request_url = \think\Config::get('domain.SDKSITE').url('Agent/Agentoareceive/agentOaChange', $mem_data);
        $do_request = \huosdk\request\Request::asyncRequst(
            $request_url, json_encode($mem_data), json_encode($_COOKIE), 15
        );

        return $do_request;
    }

    /**
     * 注册时的异步请求
     *
     * @param array $reg_data
     * $reg_data[id]
     * $reg_data[regist_ip]
     * $reg_data[reg_time]
     * $reg_data[agent_id]
     * $reg_data[app_id]
     *
     * @return \huosdk\request\请求结果
     */
    public function request_reg_agent_oa($reg_data = array()) {
        $mem_data = $reg_data;
        $mem_data['update_ip_cnt'] = 1;/* 更新ip数 */
        $mem_data['reg_cnt'] = 1;/* 更新注册数 */
        $mem_data['t_id'] = $reg_data['id'];
        $mem_data['t_name'] = 'members';/* 表名 */
        $mem_data['mem_id'] = $reg_data['id'];

        return $this->request_agent_oa($mem_data);
    }

    /**
     * 支付时的异步请求
     *
     * @param array $pay_data
     * $pay_data[id]
     * $pay_data[mem_id]
     * $pay_data[agent_id]
     * $pay_data[app_id]
     * $pay_data[real_amount]
     * $pay_data[create_time]
     *
     * @return \huosdk\request\请求结果
     */
    public function request_pay_agent_oa($pay_data = array()) {
        $mem_data = $pay_data;
        $mem_data['update_pay_mem_cnt'] = 1;/* 更新订单人数 */
        $mem_data['order_cnt'] = 1;/* 增加订单数 */
        $mem_data['t_id'] = $pay_data['id'];
        $mem_data['t_name'] = 'pay';/* 表名 */
        $mem_data['sum_money'] = $pay_data['amount'];/* 充值金额 */
        $mem_data['real_amount'] = $pay_data['real_amount'];/* 真实充值金额 */
        $mem_data['do_time'] = $pay_data['create_time'];/* 以下单时间为准 */

        return $this->request_agent_oa($mem_data);
    }
}
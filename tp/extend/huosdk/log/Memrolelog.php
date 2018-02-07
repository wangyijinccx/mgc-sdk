<?php
/**
 * Memrolelog.php UTF-8
 * 玩家角色记录表
 *
 * @date    : 2016年11月15日下午2:27:50
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2016年11月15日下午2:27:50
 */
namespace huosdk\log;

use think\Log;
use think\Db;
use think\Session;

class Memrolelog extends Huolog {
    /**
     * 自定义错误处理
     *
     * @param msg 输出的文件
     */
    private function _error($msg, $level = 'error') {
        $_info = 'log\Memrolelog Error:'.$msg;
        Log::record($_info, 'error');
    }

    /**
     * 构造函数
     *
     * @param $table_name string 数据库表名
     */
    public function __construct($table_name) {
        parent::__construct($table_name);
    }

    /**
     * 插入游戏角色数据
     *
     * @param $data [type] int 插入类型
     * @param $data [money] double 此次充值金额
     */
    public function insert(array $data) {
        $_data['mem_id'] = Session::get('id', 'user');
        $_data['app_id'] = Session::get('app_id', 'app');
//         $_data['experience'] = Session::get('experience', 'app');
        $_data['attach'] = '';
        $_data['type'] = $data['type'];
        $_data['server_id'] = Session::get('server_id', 'role');
        $_data['server_name'] = Session::get('server_name', 'role');
        $_data['role_id'] = Session::get('role_id', 'role');
        $_data['role_name'] = Session::get('role_name', 'role');
        $_data['role_level'] = Session::get('role_level', 'role');
        $_data['role_vip'] = Session::get('role_vip', 'role');
        $_data['party_name'] = Session::get('party_name', 'role');
        $_data['role_balence'] = Session::get('role_balence', 'role');
        $_data['money'] = $data['money'];
        $_data['rolelevel_ctime'] = Session::get('rolelevel_ctime', 'role');
        $_data['rolelevel_mtime'] = Session::get('rolelevel_mtime', 'role');
        $_data['create_time'] = time();
        $_rs = parent::insertGetId($_data);
        if (!$_rs) {
            return false;
        }
        $_mem_data = $_data;
        $_mem_data['agent_id'] = Session::get('agent_id', 'user');
        $_mem_data['reg_time'] = Session::get('reg_time', 'user');
        $_mem_data['userua'] = Session::get('userua', 'device');
        $_mem_data['device_id'] = Session::get('device_id', 'device');
        $_mem_data['ip'] = Session::get('ip', 'device');
        $_mem_data['from'] = Session::get('from', 'device');
        /* 添加异步回调 */
        if (\huosdk\common\Commonfunc::isOaEnable()) {
            $_ol_class = new \huosdk\oa\Oaupinfo();
            $_mem_data['id']=$_rs;
            $_ol_class->upInfo($_mem_data);
        }
        //插入记录后的逻辑
        /* 1 更新角色表 */
        /* 2 异步更新推广员业绩直接调起还是判断后调起 */
        if ($_mem_data['agent_id'] && $_mem_data['reg_time']) {
            $_post = $_mem_data;
            $_post['t_id'] = $_rs;
            $_post['t_name'] = 'mg_role_log';
            $_post['update_s_mem_cnt'] = 1;
            $_post['do_time'] = $_data['create_time'];
            $oa_class = new \huosdk\agent\Agentoa($_mem_data['agent_id']);
            $do_request = $oa_class->request_agent_oa($_post);
        }

        return true;
    }

    public function insertbyData(array $data) {
        $_rs = parent::insert($data);
        if (!$_rs) {
            return false;
        }
        //插入记录后的逻辑
        /* 1 更新角色表 */

        return true;
    }
}
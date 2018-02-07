<?php
/**
 * Userrole.php UTF-8
 * 玩家登陆接口
 *
 * @date    : 2016年8月18日下午9:47:10
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : api 2.0
 */
namespace app\player\controller\v7;

use app\common\controller\Baseplayer;
use think\Session;
use think\Db;

class Userrole extends Baseplayer {
    function _initialize() {
        parent::_initialize();
    }

    /*
     * 上传玩家角色数据
     */
    function set() {
        $_key_arr = array(
            'role_type',
            'server_id',
            'server_name',
            'role_id',
            'role_name'
        );
        $this->getParams($_key_arr);
        Session::set('role', $this->rq_data['roleinfo']);
        $_mr_class = new \huosdk\log\Memrolelog('mg_role_log');
        $_data['type'] = $this->rq_data['roleinfo']['role_type'];
        $_data['money'] = 0;
        if(!is_numeric($_data['type'])){
            return hs_player_responce(419, '参数错误 role_type');
        }
        $_mr_class->insert($_data);
        return hs_player_responce(201, '上传成功', '', $this->auth_key);
    }

    /*
     * 直播
     */
    function getWebinar() {
        //$_data['mem_id'] = Session::get('id', 'user');
        //$_data['server_id'] = Session::get('server_id', 'role');
        //$_data['app_id'] = Session::get('app_id', 'app');

        //测试
        $_data['mem_id'] = 78212;
        $_data['server_id'] = 13156;
        $_data['app_id'] = 362647;

        if (\huosdk\common\Commonfunc::isOaEnable()) {
            $_ol_class = new \huosdk\oa\Oaupinfo();
            //同步
            $_return_content = $_ol_class->getWebinarInfo($_data);
            \think\Log::write("--->oa call back", 'error');
            $_return_arr = json_decode($_return_content,true);
            \think\Log::write($_return_arr, 'error');
            if(200 == $_return_arr['code']){
                $_map['id'] = $_data['mem_id'];
                $_rs = Db::name('members')->where($_map)->select();
                \think\Log::write("--->c_member", 'error');
                \think\Log::write($_rs, 'error');
                $_data_r["account"] = $_rs[0]["username"];
                $_data_r["pass"] = $_rs[0]["wh_passwd"];
                $_data_r["webinar"] = $_return_arr["data"];
                \think\Log::write($_return_arr, 'error');
                return hs_player_responce(200, '获取直播间成功', $_data_r, $this->auth_key);
            }

            return hs_player_responce(202, '获取直播间失败');
        }
        return hs_player_responce(6, 'oa不可用');
    }

    /*
     * 情侣连麦记录
     */
    function couples() {
        /*
         * role_id  couples_role_id  $status 不能为空  role_id小  couples_role_id大
         * 结婚 a b  1
         * 离婚 a b  0
         * 二婚 a c  1
         * 必须先离婚才能复婚或者二婚
         * 先判断是结婚还是离婚，离婚查询a，b 更改状态，有一条或者多条的status都改成0，如果没有记录，属于传错数据，不管
         * 结婚查询a，b，status =1没有创建，有的话不做修改
         * 结婚的情况有可能是复婚(同一对)，但不关心之前结婚的那条数据（status = 0），重新加一条
         * 二婚（不是同一对），直接创建数据
         * 如果对方不是公司包 怎么处理？
         */
        $_key_arr = array(
            'couples_role_id',
            'status'
        );
        $_data = array();
        $this->getParams($_key_arr);
        $_role = Session::get('role_id', 'role');
        $_app = Session::get('app_id', 'app');
        if(empty($_role) || empty($_app)){
            return hs_player_responce(400, '获取角色信息失败');
        }
        $_couples_role = $this->rq_data["couples_role_id"];
        if ($_role < $_couples_role) {
            $_data["role_id"] = $_role;
            $_data["couples_role_id"] = $_couples_role;
        } else {
            $_data["role_id"] = $_couples_role;
            $_data["couples_role_id"] = $_role;
        }
        $_data["status"] = $this->rq_data["status"];
        $_data["app_id"] = $_app;
        \think\log::write($_data, "error");
        if (0 == $_data["status"]) {
            //离婚
            $_data_0["role_id"] = $_data["role_id"];
            $_data_0["couples_role_id"] = $_data["couples_role_id"];
            $_data_0["app_id"] = $_data["app_id"];
            Db::name('couples_relation')->where($_data_0)->update(['status' => 0, 'update_time' => time()]);

            return hs_player_responce(200, '离婚成功');
        } else if (1 == $_data["status"]) {
            //结婚或者二婚
            //是否需要判断a或者b有结婚状态，有的话就提示先离婚。不需要
            $_return_date = Db::name('couples_relation')->where($_data)->select();
            if (0 == count($_return_date)) {
                //创建
                $_data_1 = $_data;
                $_data_1["channel"] = $_app."-".$_data["role_id"]."-".$_data["couples_role_id"];
                $_data_1["create_time"] = time();
                $_data_1["update_time"] = time();
                Db::name('couples_relation')->insert($_data_1);
                $_channel["channel"] = $_data_1["channel"];
                $_channel["portrait"] = "";

                return hs_player_responce(200, '创建连麦信息成功', $_channel, $this->auth_key);

            } else if (1 == count($_return_date)) {
                $_channel["channel"] = $_return_date[0]["channel"];
                $_channel["portrait"] = "";

                return hs_player_responce(200, '创建连麦信息成功', $_channel, $this->auth_key);
            } else {
                //结婚每对只能有一条记录
                return hs_player_responce(500, '服务器数据异常');
            }
        } else {
            return hs_player_responce(419, '参数错误 status');
        }
    }

    /*
     * 连麦
     */
    function connect() {
        /*
         * 参数 mem_id
         * 登陆判断是否有连麦功能 有的话返回channel 没有空
         * 请求连麦 有的话返回channel 没有空
         * 结婚的只能有一条数据status=1
         */
        $_role_id = Session::get('role_id', 'role');
        $_app = Session::get('app_id', 'app');
        if(empty($_role_id)){
            return hs_player_responce(400, '获取角色信息失败');
        }
        $_return_date = Db::name('couples_relation')->where('role_id|couples_role_id', '=', $_role_id)
                          ->where(['status' =>1,'app_id'=>$_app])->select();
        if (empty($_return_date)) {
            return hs_player_responce(201, '该玩家没有结婚');
        } else {
            $_channel["channel"] = $_return_date[0]["channel"];
            $_channel["portrait"] = "";

            return hs_player_responce(200, '获取连麦信息成功', $_channel, $this->auth_key);
        }
    }
}
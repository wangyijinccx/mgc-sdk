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
namespace huosdk\oa;
class Oareg extends Oa {
    /**
     * @param array $login_data
     *
     * @return array
     */
    public function mem_reg($reg_data = array()) {
        if (empty($reg_data)) {
            return array(
                'code' => '403',
                'msg'  => '参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!(parent::g_oa())) {
            return array(
                'code' => '201',
                'msg'  => '未对接oa mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['uersname']) || empty($reg_data['uersname'])) {
            return array(
                'code' => '404',
                'msg'  => 'uersname参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['reg_time']) || empty($reg_data['reg_time'])) {
            return array(
                'code' => '405',
                'msg'  => 'reg_time 参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['app_id']) || empty($reg_data['app_id'])) {
            return array(
                'code' => '406',
                'msg'  => 'app_id参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['agentname']) || empty($reg_data['agentname'])) {
            return array(
                'code' => '407',
                'msg'  => 'agentname参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['login_ip']) || empty($reg_data['login_ip'])) {
            return array(
                'code' => '408',
                'msg'  => 'login_ip参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['device_id']) || empty($reg_data['device_id'])) {
            return array(
                'code' => '408',
                'msg'  => 'device_id参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['from']) || empty($reg_data['from'])) {
            return array(
                'code' => '409',
                'msg'  => 'from参数为空 mem_reg',
                'data' => ''
            );
        }
        if (!isset($reg_data['userua']) || empty($reg_data['userua'])) {
            return array(
                'code' => '410',
                'msg'  => 'userua参数为空 mem_reg',
                'data' => ''
            );
        }
        $_ecodeusername = urlencode($reg_data['uersname']);
        $_ecodeagentname = urlencode($reg_data['agentname']);
        $_ecodelogin_ip = urlencode($reg_data['login_ip']);
        $_ecodedevice_id = urlencode($reg_data['device_id']);
        $_ecodeuserua = urlencode($reg_data['userua']);
        $timestamp = time();
        $data_str = 'plat_id='.parent::plat_id().'&app_id='.$reg_data['app_id'].'&timestamp='.$timestamp;
        $data_str .= '&uersname='.$_ecodeusername.'&login_time='.$reg_data['login_time'];
        $data_str .= '&agentname='.$_ecodeagentname.'&login_ip='.$_ecodelogin_ip;
        $data_str .= '&device_id='.$_ecodedevice_id.'&from='.$reg_data['from'].'&userua='.$_ecodeuserua;
        $post_data_str = $data_str.'&sign_type='.parent::sign_type();
        $sign_md5_str = $data_str.'&PLAT_SECURE_KEY='.parent::plat_secure_key();
        $sign = md5($sign_md5_str);
        $post_data_str .= '&sign='.$sign;
        $return= parent::request_oa($post_data_str, parent::mem_login_url());
        return array(
            'code'=>200,
            'msg'=>'完成 mem_reg',
            'data'=>$return
        );
    }
}

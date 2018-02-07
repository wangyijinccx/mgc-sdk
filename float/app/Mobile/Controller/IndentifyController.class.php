<?php

/**
 * IndentifyController.class.php UTF-8
 * 实名认证
 * @date: 2017年5月10日
 * 
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author : ou <ozf@1tsdk.com>
 * @version : 1.0
 */
namespace Mobile\Controller;
use Common\Controller\MobilebaseController;

class IndentifyController extends MobilebaseController
{
    function _initialize() {
        parent::_initialize();
    }
    // 浮点用户信息首页
    public function index() {
        $this->assign("title", '实名认证');
	    $mem_id = sp_get_current_userid();
	    $_user_data = M("members")->field("id,truename,idcard")->where(array("id"=>$mem_id))->find();
        $_user_data["showidcard"] = "";
	    if (!empty($_user_data['idcard'])){
            $_user_data["showidcard"] = substr($_user_data["idcard"],0,4)."****".substr($_user_data["idcard"],-4);
        }
        if (empty($_user_data["id"])) {
            $_user_data["id"] = $mem_id;
        }
	    $this->assign("userdata",$_user_data);
        $this->display();
	}

	public function set(){
        $mem_id = I('post.mem_id/d', 0);
        $realname = I('post.realname/s', '');
        $idcard = I('post.idcard/s', '');
        if (empty($mem_id) || empty($realname) || empty($idcard)){
            $this->error("参数错误");
        }
        $_data["truename"] = $realname;
        $_data["idcard"] = $idcard;
        $_data["update_time"] = time();
        $rs = M("members")->where(array("id"=>$mem_id))->save($_data);
        $url = U('Mobile/User/index');
        if($rs){
            $this->success("设定成功", $url);
        }
    }
}

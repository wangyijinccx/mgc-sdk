<?php
namespace Front\Controller;

use Common\Controller\AgentPublicController;

class RegController extends AgentPublicController {
    public function _initialize() {
        parent::_initialize();
    }

    //安卓注册页面
    public function ad_reg() {
        $name = I('name', '');
        $record = $this->getGame($name);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
//        print_r($record);exit;
        $this->assign("game_record", $record);
        $this->display();
    }

    //六界凌霄安卓注册页面
  /*  public function ljlx() {
        $name = I('name', '');
        $record = $this->getGame($name);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
//        print_r($record);exit;
        $this->assign("game_record", $record);
        $this->display();
    }*/
    
    //仙域3D安卓注册页面
   /* public function xy3d() {
        $name = I('name', '');
        $record = $this->getGame($name);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
        //print_r($record);die;
        $this->assign("game_record", $record);
        $this->display();
    }*/

    //ios注册页面
    public function ios_reg() {
        $name = I('name', '');
        $record = $this->getGame($name);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
        $this->assign("game_record", $record);
        $this->display();
    }


    //沙城无双注册页面
    public function scws() {
        $name = I('name', '');
        if (empty($name)) {
            return false;
        }
        $de_name = base64_decode($name);
        if (!empty($de_name)) {
            $where = ['agentgame' => $de_name];
            $gameResult = M('agent_game')->where($where)->find();
            $map['adid']  = array('eq',$gameResult["app_id"]);
            $map['iosid']  = array('eq',$gameResult["app_id"]);
            $map['_logic'] = 'OR';
            $assoResult = M("game_associated")->where($map)->find();
            $gameWhere['_string'] = '(ag.app_id = '.$assoResult["adid"].' and ag.agent_id='
                .$gameResult["agent_id"].') or( ag.app_id = '.$assoResult["iosid"].' and ag.agent_id='
                                    .$gameResult["agent_id"].')';
        }else{
            return false;
        }
        $record = $this->getGameNew($gameWhere);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
        //print_r($record);die;
        $this->assign("game_record", $record);
        $this->display();
    }



    //仙域3d注册页面
    public function xy3d() {
        $name = I('name', '');
        if (empty($name)) {
            return false;
        }
        $de_name = base64_decode($name);
        if (!empty($de_name)) {
            $where = ['agentgame' => $de_name];
            $gameResult = M('agent_game')->where($where)->find();
            $map['adid']  = array('eq',$gameResult["app_id"]);
            $map['iosid']  = array('eq',$gameResult["app_id"]);
            $map['_logic'] = 'OR';
            $assoResult = M("game_associated")->where($map)->find();
            $gameWhere['_string'] = '(ag.app_id = '.$assoResult["adid"].' and ag.agent_id='
                                    .$gameResult["agent_id"].') or( ag.app_id = '.$assoResult["iosid"].' and ag.agent_id='
                                    .$gameResult["agent_id"].')';
        }else{
            return false;
        }
        $record = $this->getGameNew($gameWhere);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
        //print_r($record);die;
        $this->assign("game_record", $record);
        $this->display();
    }


    //六界凌霄注册页面
    public function ljlx() {
        $name = I('name', '');
        if (empty($name)) {
            return false;
        }
        $de_name = base64_decode($name);
        if (!empty($de_name)) {
            $where = ['agentgame' => $de_name];
            $gameResult = M('agent_game')->where($where)->find();
            $map['adid']  = array('eq',$gameResult["app_id"]);
            $map['iosid']  = array('eq',$gameResult["app_id"]);
            $map['_logic'] = 'OR';
            $assoResult = M("game_associated")->where($map)->find();
            $gameWhere['_string'] = '(ag.app_id = '.$assoResult["adid"].' and ag.agent_id='
                                    .$gameResult["agent_id"].') or( ag.app_id = '.$assoResult["iosid"].' and ag.agent_id='
                                    .$gameResult["agent_id"].')';
        }else{
            return false;
        }
        $record = $this->getGameNew($gameWhere);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
        //print_r($record);die;
        $this->assign("game_record", $record);
        $this->display();
    }

    public function getGameNew($gameWhere) {
        $game_info = M('agent_game')
            ->field(
                "ag.*,g.name as gamename,g.classify,IFNULL(gi.mobile_icon,g.icon) icon,g.initial,agr.agent_rate,gi.bgthumb,gi.image,gi.size gamesize,gi.yiosurl"//gv.packageurl downurl,
            )
            ->alias('ag')
            ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_info gi ON gi.app_id=g.game_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id")
            ->join("LEFT JOIN ".C('DB_PREFIX')."agent_game_rate agr ON agr.ag_id=ag.id")
            ->where($gameWhere)
            ->select();

        if (count($game_info) == 0) {
            return false;
        }
        $game_record = $game_info[0];
        $game_record['cates'] = $this->getAppCates($game_record['app_id']);
        $game_record['image_arr'] = $this->getImages($game_record['image']);
        $game_record['gamename'] = strstr($game_record['gamename'],"-",true);
        if(count($game_info) == 2){
            if(4 == $game_info[1]['classify']){
                $game_record["adurl"] = DOWNSITE.$game_record['url'];
                $game_record["iosurl"] = $game_info[1]['yiosurl'];
                $game_record["aname"] = base64_encode($game_info[1]["agentgame"]);
            }else{
                $game_record["adurl"] = DOWNSITE.$game_info[1]['url'];
                $game_record["iosurl"] = $game_record['yiosurl'];
                $game_record["aname"] = base64_encode($game_info[0]["agentgame"]);
            }
        }else if(count($game_info) == 1){
            if (4 == $game_record['classify']) {
                $game_record['iosurl'] = $game_record['yiosurl'];
                $game_record["aname"] = base64_encode($game_record["agentgame"]);
            } else {
                $game_record['adurl'] = DOWNSITE.$game_record['url'];
                $game_record["aname"] = base64_encode($game_record["agentgame"]);
            }
        }
        return $game_record;
    }


    public function getGame($name = '') {
        if (empty($name)) {
            return false;
        }
        $de_name = base64_decode($name);
        if (!empty($de_name)) {
            $where = ['agentgame' => $de_name];
            $game_record = M('agent_game')
                ->field(
                    "ag.*,g.name as gamename,g.classify,IFNULL(gi.mobile_icon,g.icon) icon,g.initial,agr.agent_rate,gi.bgthumb,gi.image,gi.size gamesize,gi.yiosurl"//gv.packageurl downurl,
                )
                ->alias('ag')
                ->join("LEFT JOIN ".C('DB_PREFIX')."game g ON g.id=ag.app_id")
                ->join("LEFT JOIN ".C('DB_PREFIX')."game_info gi ON gi.app_id=g.game_id")
                ->join("LEFT JOIN ".C('DB_PREFIX')."game_version gv ON gv.app_id=ag.app_id")
                ->join("LEFT JOIN ".C('DB_PREFIX')."agent_game_rate agr ON agr.ag_id=ag.id")
                ->where($where)
                ->find();
            if (empty($game_record)) {
                return false;
            }
            $game_record['cates'] = $this->getAppCates($game_record['app_id']);
            $game_record['image_arr'] = $this->getImages($game_record['image']);
            if (4 == $game_record['classify']) {
                $game_record['downurl'] = $game_record['yiosurl'];
            } else {
                $game_record['downurl'] = DOWNSITE.$game_record['url'];
            }
            return $game_record;
        } else {
            return false;
        }
    }

    // 获取游戏的类型
    private function getAppCates($appid) {
        $type_list = M('game')->where(array("id" => $appid))->getField("type");
        if ($type_list) {
            $items = M('game_type')
                ->field("name")
                ->where(array("id" => array("IN", $type_list)))
                ->select();
            $txt = '';
            foreach ($items as $key => $value) {
                $txt .= " ".$value['name'];
            }

            return $txt;
        }
    }

    //转换截图
    private function getImages($image) {
        if (empty($image)) {
            return [];
        }

        return json_decode($image, true);
    }

    //注册
    public function reg_do() {
        $name = I('game_name');
        $record = $this->getGame($name);
        if (!$record) {
            $this->ajaxReturn(array('error' => 1, 'msg' => '参数错误'));
        }
        $data = [
            'username' => I('username'),
            'password' => I('password'),
        ];
        $this->checkRegdata($data);
        $this->checkUsername($data['username']);
        $mem_data['username'] = $data['username'];
        $mem_data['password'] = $this->authPwd($data['password']);
        $mem_data['reg_time'] = time();
        $mem_data['update_time'] = $mem_data['reg_time'];
        $mem_data['status'] = 2;
        $mem_data['agent_id'] = $record['agent_id'];
        $mem_data['app_id'] = $record['app_id'];
        $mem_data['agentgame'] = base64_decode($name);
        $mem_data['from'] = 2;
        $mem_data['regist_ip'] = $this->get_client_ip();
        $_mem_id = M('members')->add($mem_data);
        if (!$_mem_id) {
            $this->ajaxReturn(array('error' => 1, 'msg' => '注册失败'));
        }
        session('m_id',$_mem_id);
        $this->ajaxReturn(array('error' => 0, 'msg' => '注册成功'));
    }

    //验证提交数据
    public function checkRegdata($data) {
        if (empty($data['username'])) {
            $this->ajaxReturn(array('error' => 1, 'msg' => '请输入用户名'));
        }
        if (empty($data['password'])) {
            $this->ajaxReturn(array('error' => 1, 'msg' => '请输入密码'));
        }
        if (strlen($data['username']) < 6 || strlen($data['username']) > 18) {
            $this->ajaxReturn(array('error' => 1, 'msg' => '用户名长度只能使用 6-18个字符'));
        }
        if (strlen($data['password']) < 6 || strlen($data['password']) > 18) {
            $this->ajaxReturn(array('error' => 1, 'msg' => '密码长度只能使用 6-18个字符'));
        }
    }

    //验证是否注册
    public function checkUsername($username) {
        $_map['username'] = $username;
        $_mem_id = M('members')->where($_map)->getField('id');
        if (!empty($_mem_id)) {
            $this->ajaxReturn(array('error' => 1, 'msg' => '用户已存在'));
        }
    }

    //密码转化
    public function authPwd($pw, $authcode = '') {
        if (empty($authcode)) {
            $authcode = C('AUTHCODE');
        }
        $_result = md5(md5($authcode.$pw).$pw);

        return $_result;
    }

    //注册成功跳转页面
    public function to_download() {
        $session_id = session('m_id');
        if(empty($session_id)) {
            exit("请先注册 :)");
        }
        $name = I('name');
        $record = $this->getGame($name);
        if (!$record) {
            exit("请复制正确的链接地址 :)");
        }
//        print_r($record);exit;
        $this->assign("game_record", $record);
        $this->display();
    }

    // 获取IP
    public static function get_client_ip() {
        if (getenv('HTTP_CLIENT_IP')) {
            $client_ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR')) {
            $client_ip = getenv('REMOTE_ADDR');
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $client_ip;
    }
}


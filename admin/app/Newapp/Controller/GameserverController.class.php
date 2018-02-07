<?php
/* 
 *  @time 2017-1-19 11:10:19
 *  @author 严旭
 */
namespace Newapp\Controller;

use Common\Controller\AdminbaseController;

class GameserverController extends AdminbaseController {
    public $obj;
    public $model;

    function _initialize() {
        parent::_initialize();
        $this->obj = new \Huosdk\UI\Filter();
        $this->model = M('game_server');
    }

    public function index() {
        $this->assign("app_select", $this->obj->app_select());
        $where = array();
        $f_obj = new \Huosdk\Where();
        $f_obj->get_simple($where, "app_id", "so.app_id");
        $f_obj->get_simple_like($where, "ser_name", "so.ser_name");
        $f_obj->get_simple($where, "ser_code", "so.ser_code");
        $count = count($this->getList($where));
        $page = $this->page($count, 10);
        $items = $this->getList($where, $page->firstRow, $page->listRows);
        $this->assign("items", $items);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", $_GET);
        $this->display();
    }

    public function add() {
        $this->assign("app_select", $this->obj->app_select());
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $info = $this->getList(array("so.id" => $id), 0, 1);
        $this->assign("data", $info[0]);
        $_GET['app_id'] = $info[0]['app_id'];
        $this->assign("app_select", $this->obj->app_select());
        $status_choose_txt = $this->statusInput($info[0]['status']);
        $this->assign("status_choose_txt", $status_choose_txt);
        $this->display();
    }

    public function addPost() {
        $this->commonFilter();
        $status = I('status');
        $app_id = I('app_id');
        $ser_name = I('ser_name');
        $ser_code = I('ser_code');
        $ser_desc = I('ser_desc');
        $start_time = I('start_time');
        $data = array();
        $data['app_id'] = $app_id;
        $data['ser_name'] = $ser_name;
        $data['ser_code'] = $ser_code;
        $data['status'] = $status;
        $data['ser_desc'] = htmlspecialchars($ser_desc);
        $data['start_time'] = strtotime($start_time);
        $data['is_delete'] = 2;
        $_old_info=$this->model->where(['ser_desc'=>$data['ser_code'],'app_id'=>$data['app_id']])->find();
        if(!empty($_old_info)&&isset($_old_info['is_delete'])&&2==$_old_info['is_delete']){
            $this->error("添加失败!,区服code重复");/* 跟未删除冲突 */
        }
        $add_result = $this->model->add($data);
        $_oa_data=$data;
        if (!$add_result&&!empty($_old_info)&&isset($_old_info['is_delete'])&&1==$_old_info['is_delete']) {
            /* 跟已删除冲突  重新启用  */
            $this->model->where(array("id" => $_old_info['id']))->save($data);
            $_oa_data['update_time']=time();
            $_oa_data['server_id']=$_old_info['id'];
            $_do_request = \Huosdk\Request::update_oa_game_server($_oa_data);
            if(empty($_do_request)||!isset($_do_request['code'])||$_do_request['code']!=200){
                \Think\Log::write(is_array($_do_request)?json_encode($_do_request):$_do_request, 'debug');
            }

        }else{
            if(!$add_result){
                $this->error("添加失败");
            }
            $_oa_data['create_time']=time();
            $_oa_data['server_id']=$add_result;
            $_do_request = \Huosdk\Request::add_oa_game_server($_oa_data);
            if(empty($_do_request)||!isset($_do_request['code'])||$_do_request['code']!=200){
                \Think\Log::write(is_array($_do_request)?json_encode($_do_request):$_do_request, 'debug');
            }
        }


        $this->success("添加成功", U('Newapp/Gameserver/index'));
    }

    public function commonFilter() {
        if (!I('app_id')) {
            $this->error("请选择游戏");
        }
        if (!I('ser_name')) {
            $this->error("请输入开服名称");
        }
        if (!I('ser_code')) {
            $this->error("请输入开服code");
        }
        if (!I('start_time')) {
            $this->error("请输入开服时间");
        }
        if (!I('ser_desc')) {
            $this->error("请输入开服描述");
        }
        if (!I('status')) {
            $this->error("请选择开服状态");
        }
    }

    public function editPost() {
        $id = I('id');
        if (!$id) {
            $this->error("参数有误");
        }
        $this->commonFilter();
        $data = array();
        $data['app_id'] = I('app_id');
        $data['ser_name'] = I('ser_name');
        $data['ser_code'] = I('ser_code');
        $ser_code_old = I('ser_code_old');
        $data['status'] = I('status');
        $data['ser_desc'] = htmlspecialchars(I('ser_desc'));
        $data['start_time'] = strtotime(I('start_time'));
        $result = $this->model->where(array("id" => $id))->save($data);
        if (!$result) {
            $this->error("修改失败");
        }
        if($ser_code_old!=$data['ser_code']){
            $_del_data['update_time']=time();
            $_del_data['server_id']=$id;
            $_del_data['is_delete']=1;
            $_del_data['ser_code']=$ser_code_old;
            $_del_data['server_id']=$id;
            $_del_data['ser_name']=$data['ser_name'];
            $_del_data['app_id']=$data['app_id'];
            $_del_request = \Huosdk\Request::update_oa_game_server($_del_data);
            if(empty($_del_request)||!isset($_del_request['code'])||$_del_request['code']!=200){
                \Think\Log::write(is_array($_del_request)?json_encode($_del_request):$_del_request, 'debug');
            }
        }
        $_oa_data=$data;
        $_oa_data['update_time']=time();
        $_oa_data['server_id']=$id;
        $_do_request = \Huosdk\Request::update_oa_game_server($_oa_data);
        if(empty($_do_request)||!isset($_do_request['code'])||$_do_request['code']!=200){
            \Think\Log::write(is_array($_do_request)?json_encode($_do_request):$_do_request, 'debug');
        }
        $this->success("修改成功", U('Newapp/Gameserver/index'));
    }

    public function getList($where = array(), $start = 0, $limit = 0) {
        $items = $this->model->alias('so')
                             ->field("g.name as game_name,g.icon as game_icon,so.*")
                             ->join("LEFT JOIN ".C("DB_PREFIX")."game g ON g.id=so.app_id")
                             ->where(array("so.is_delete" => 2))
                             ->where($where)
                             ->limit($start, $limit)
                             ->order("so.id desc")
                             ->select();
        $status_data = array("1" => "预告", "2" => "已开服");
        foreach ($items as $key => $value) {
            $items[$key]['start_time'] = date("Y-m-d H:i:s", $value['start_time']);
            $items[$key]['ser_desc_striped'] = mb_substr(($value['ser_desc']), 0, 50);
            $items[$key]['status_txt'] = $status_data[$value['status']];
        }

        return $items;
    }

    public function deletePost() {
        $id = I('id');
        $_oa_data=$this->model->where(array("id" => $id))->find();
        $this->model->where(array("id" => $id))->setField("is_delete", 1);
        $_oa_data['update_time']=time();
        $_oa_data['server_id']=$id;
        $_oa_data['is_delete']=1;
        $_do_request = \Huosdk\Request::update_oa_game_server($_oa_data);
        if(empty($_do_request)||!isset($_do_request['code'])||$_do_request['code']!=200){
            \Think\Log::write(is_array($_do_request)?json_encode($_do_request):$_do_request, 'debug');
        }
        $this->success("删除成功");
    }

    public function statusInput($status) {
        $status_data = array("1" => "预告", "2" => "已开服");
        $txt = '';
        foreach ($status_data as $key => $value) {
            $checked = '';
            if ($status == $key) {
                $checked = "checked='checked'";
            }
            $txt .= "<input type='radio' name='status' $checked value='$key' />".$value;
        }

        return $txt;
    }
    /* 找出传日志中已有区服添加到区服管理中 */
    public function addOldServer(){
        $where=" NOT EXISTS (select app_id,ser_code from ".C('DB_PREFIX')
               ."game_server where g.app_id=app_id AND ser_code =g.server_id) ";
        $_server_code_list=M('mg_role_log')->alias('g')->where($where)->field('DISTINCT(server_id),app_id,server_name')->select();
        $do_list=[];
        if(!empty($_server_code_list)&&is_array($_server_code_list)){
            foreach($_server_code_list as $item){
                if(isset($item['server_id'])&&isset($item['app_id'])){
                   $_check_app_id=$this->checkOaGame($item['app_id']);
                    if($_check_app_id){

                    }
                    $_map=['ser_code'=>$item['server_id'],'app_id'=>$item['app_id']];
                    $game_server_info=M('game_server')->where($_map)->select();
                    if(empty($game_server_info)){
                        $_oa_data=$_map;
                        $_oa_data['ser_name']=isset($item['server_name'])?$item['server_name']:'';
                        $_oa_data['start_time']=time();
                        $add_result = $this->model->add($_oa_data);
                        $_oa_data['create_time']=$_oa_data['start_time'];
                        $_oa_data['start_time']=$_oa_data['create_time'];
                        $_oa_data['server_id']=$add_result;
                        $do_list[] = \Huosdk\Request::add_oa_game_server($_oa_data);
                    }else{
                        $_oa_data=$_map;
                        $_oa_data['ser_name']=isset($item['server_name'])?$item['server_name']:'';
                        $_oa_data['ser_desc']='ser_code:'.$_map['ser_code'].'ser_name:'.$_oa_data['ser_name'];
                        $_oa_data['create_time']=time();
                        $_oa_data['server_id']=isset($game_server_info['id'])?$game_server_info['id']:'404';
                        $_oa_data['start_time']=isset($game_server_info['start_time'])?$game_server_info['start_time']:$_oa_data['create_time'];
                        $do_list[] = \Huosdk\Request::add_oa_game_server($_oa_data);
                    }
                }

            }
        }
        echo '检测完成,请刷新当前页面';
        exit;
    }

    /**
     * 同步oa区服信息
     */
    public function checkOaGameServer(){
        $_server_id = I("id/d");
        $g_data = M('game_server')->where(array('id' => $_server_id))->find();
        $g_data['create_time']=time();
        $g_data['server_id']=$g_data['id'];
        $do_request = \Huosdk\Request::add_oa_game_server($g_data);
        $_data_content=is_string($do_request);
        /*
         * array (
  'code' => 200,
  'msg' => '处理完成',
  'data' => '{"code":200,"msg":"添加区服失败","data":{"code":606,"msg":"创建失败,ser_code重复","data":47}}',
)
         * */
        $_data_content=isset($do_request['data'])?(is_array($do_request['data'])?json_encode($do_request['data']):$do_request['data']):(is_array($do_request)?json_encode($do_request):$do_request);
        if(strpos($_data_content,"添加区服失败")){
            $jsd=json_decode($_data_content,true);
            if(!empty($jsd)&&isset($jsd['data'])&&!empty($jsd['data'])&&isset($jsd['data']['code'])&&606==$jsd['data']['code']){
                $_data_content='已有该区服';
            }
        }
        echo $_data_content;
        exit;

    }
    public function checkOaGame($app_id=0){
        $g_data = M('game')->where(array('id' => $app_id))->find();
        $gv_data = M('game_version')->where(array('app_id' => $app_id,'status'=>2))->find();
        $_oa_data = $g_data;
        $_oa_data['app_id'] = $app_id;
        $_oa_data['version'] = $gv_data['version'];
        $do_request = \Huosdk\Request::add_oa_game($_oa_data);
        return $do_request;
    }
}
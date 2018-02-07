<?php
/**
 * CpController.class.php UTF-8
 * Cp后台管理
 *
 * @date    : 2017/5/2 11:05
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : kangshihao <256ksh@163.com>
 * @version : HUOSDK 7.0
 */
namespace Sdk\Controller;

use Common\Controller\AdminbaseController;

class CpController extends AdminbaseController {
    protected $cp;

    function _initialize() {
        parent::_initialize();
        $this->cp = D("Common/cp");
    }

    function index() {
        $cp_data = $this->cp->select();
        $count = $this->cp->count();
        $page = $this->page($count, 20);
        $this->assign('page', $page);
        $this->assign('cp_data', $cp_data);
        $this->display();
    }

    /*
     * 处理添加
     * */
    function addPost() {
        $cp_data['company_name'] = I('post.company_name');
        $cp_data['contacter'] = I('post.contacter');
        $cp_data['mobile'] = I('post.mobile');
        $cp_data['position'] = I('post.position');
        $cp_data['create_time'] = time();
        $cp_data['update_time'] = $cp_data['create_time'];
        if (false != $this->cp->create($cp_data)) {
            $this->cp->add();

            return $this->ajaxReturn(['error' => '1', 'msg' => '添加成功！']);
        } else {
            return $this->ajaxReturn(['error' => '0', 'msg' => $this->cp->getError()]);
        }
    }

    /*
     * 删除处理
     * */
    function deleteCP() {
        $cp_id = I('cp_id', 0);
        $result = $this->cp->where("id = %d", $cp_id)->delete();
        if (false != $result) {
            $this->success("删除成功", U("Cp/index"));
            exit;
        }
        $this->error('删除失败.');
    }

    function editCp() {
        $cp_id = I('cp_id', 0);
        $cp_data = $this->cp->where("id = %d", $cp_id)->find();
        if (false != $cp_data) {
            $this->assign('cp_data', $cp_data);
            $this->display();
        } else {
            $this->error('cp_id有误！');
        }
    }

    /*
     * 修改处理
     * */
    function editPost() {
        $cp_id = I('post.cp_id', 0);
        $company_name = I('post.company_name');
        if (empty($company_name)) {
            $this->error('cp名称为空');
        } else {
            $result = $this->cp->where("company_name = '%s' and  id != '%d'", $company_name, $cp_id)->find();
            if (isset($result)) {
                $this->error('cp名称已经存在');
            }
        }
        $cp_data['contacter'] = I('post.contacter');
        $cp_data['mobile'] = I('post.mobile');
        $cp_data['position'] = I('post.position');
        $cp_data['update_time'] = time();
        if (false != $this->cp->create($cp_data)) {
            $cp_data['company_name'] = $company_name;
            $result = $this->cp->where("id = %d", $cp_id)->save($cp_data);
            if ($result > 0) {
                $this->success("修改成功", U("Cp/index"));
            } else {
                $this->error('修改失败');
            }
        } else {
            $this->error($this->cp->getError());
        }
    }
}
<?php

namespace app\oa\controller;

use think\Db;

class OaCheckUsername extends Oacallback {
    function _initialize() {
        parent::_initialize();
        $this->checkUsername();
        if (isset($this->param['user_name']) && !empty($this->param['user_name'])) {
            $this->checkUserByUsername($this->param['user_name']);
        }
    }

    /**
     * @return $this
     */
    function checkUsername() {
        if (empty($this->param['user_name'])) {
            return hs_api_responce('411', '用户名为空');
        }
    }

    /**
     * 检测用户名
     *
     * @param $legion_name
     *
     * @return $this
     */
    function checkUserByUsername($user_name) {
        $_legion_name = $user_name;
        $_rs = Db::name('users')->where('user_login', $_legion_name)->find();
        if (!empty($_rs)) {
            hs_api_json('200', '该渠道存在');
        } else {
            hs_api_json('201', '该渠道不存在');
        }
    }
}

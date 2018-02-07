<?php
/**
 * Created by PhpStorm.
 * User: Ksh
 * Date: 2017/6/26
 * Time: 11:12
 */

namespace app\oa\controller;

use huosdk\player\Member;
use think\Db;

class OaLogin extends Oacallback {
    function _initialize() {
        parent::_initialize();

        return $this->lockuser($this->param['usernames'], $this->param['status']);
    }

    public function index() {
    }

    private function lockuser($usernames, $status) {
        if (empty($usernames)) {
            return hs_api_json('201', 'username不能为空');
        }
        if (empty($status)) {
            return hs_api_json('201', 'status不能为空');
        }
        $username_arr = json_decode($usernames, true);
        if (!is_array($username_arr)) {
            return hs_api_json('201', 'username至少填写一个');
        }
        $map['username'] = array('in', $username_arr);
        $_rs = Db('members')
            ->where($map)
            ->setField('status', $status);
        if (false !== $_rs) {
            return hs_api_json('200', '修改用户状态成功');
        }
        return hs_api_json('201', '修改用户状态失败');
    }
}

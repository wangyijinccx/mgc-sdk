<?php

namespace app\oa\controller;

use think\Db;

class OaCheckAgent extends Oacallback {
    function _initialize() {
        parent::_initialize();
//        $this->checkLegion();
        if (isset($this->param['agent_name']) && !empty($this->param['agent_name'])) {
            $this->checkAgentname();
            $this->checkAgentByAgentnameAndLegionname($this->param['legion_name'], $this->param['agent_name']);
        } else {
            $this->checkAgentByAgentname($this->param['legion_name']);
        }
    }

    /**
     * @return $this
     */
    function checkAgentname() {
        if (empty($this->param['agent_name'])) {
            return hs_api_responce('410', '推广员名称为空');
        }
    }

    /**
     * @return $this
     */
    function checkLegion() {
        if (empty($this->param['legion_name'])) {
            return hs_api_responce('411', '军团长名称为空');
        }
    }

    /**
     * 检测军团长
     *
     * @param $legion_name
     *
     * @return $this
     */
    function checkAgentByAgentname($legion_name) {
        $_legion_name = $legion_name;
        $_rs = Db::name('users')->where('user_login', $_legion_name)->where('user_type', 6)
                 ->find();
        if (!empty($_rs)) {
            hs_api_json('200', '军团长存在');
        } else {
            hs_api_json('201', '军团长不存在');
        }
    }

    /**
     * @param $_legion_name
     * @param $_agent_name
     *
     * @return $this
     */
    function checkAgentByAgentnameAndLegionname($_legion_name, $_agent_name) {
        $_legion_info = Db::name('users')->where('user_login', $_legion_name)->where('user_type', 6)->find();
        if (!empty($_legion_info)) {
            $_agent_info = Db::name('users')->where('user_login', $_agent_name)->where('user_type', 7)->find();
            if (!empty($_agent_info)) {
                if ($_agent_info['ownerid'] == $_legion_info['id']) {
                    return hs_api_json('200', '推广员不存在');
                }

                return hs_api_json('201', '军团长和推广员关系错误');
            } else {
                return hs_api_json('201', '推广员不存在');
            }
        } else {
            return hs_api_json('201', '军团长不存在');
        }
    }
}

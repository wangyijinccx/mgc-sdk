<?php

namespace Common\Model;
class  CpModel extends CommonModel
{
    //自动验证
    protected $_validate
        = array(
            //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
            array('company_name', 'require', '公司名称不能为空！', 0, 'regex', CommonModel:: MODEL_BOTH),
            array('contacter', 'require', '联系人不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH),
            array('mobile', 'checkMobile', '手机格式不正确！', 1, 'callback', CommonModel::MODEL_BOTH),
            array('position', 'require', '职位不能为空！', 1, 'regex', CommonModel::MODEL_BOTH),
            array('company_name', 'require', '公司名称已存在！', 0, 'unique', CommonModel:: MODEL_INSERT),

        );

    protected function _before_write(&$data)
    {
        parent::_before_write($data);
    }

    function checkMobile($data)
    {
        $checkExpressions = "/^1[34578]\d{9}$/";
        if (false == preg_match($checkExpressions, $data)) {
            return false;
        }

        return true;
    }

    function getDate()
    {
        return date('Y-m-d H:i:s');
    }

}
<?php
namespace app\oa\controller;

use think\Controller;

class oa extends Controller {
    protected $param;
    protected $oa_class;

    protected function _initialize() {
        $this->param = $this->request->param();
        $_Arr=$this->request->param();
        \think\Log::write($_Arr,'error');
        $this->oa_class = new \huosdk\oa\Oa();
        $this->checkParam();
    }

    /**
     * 校验参数 仅做合法性验证
     *
     * @return bool
     */
    protected function checkParam() {
        $this->checkPlatId();
        $this->checkSign();
        $this->checkSignType();
      //  $this->checkTimestamp();
    }

    /** 验证平台id
     *
     * @return $this|bool
     */
    public function checkPlatId() {
        if (!isset($this->param['plat_id'])||empty($this->param['plat_id'])) {
            return hs_api_responce('408', '平台id错误');
        }
        if (!isset($this->oa_class->oa_conf) || !isset($this->oa_class->oa_conf['PLAT_ID'])
            || $this->oa_class->oa_conf['PLAT_ID'] != $this->param['plat_id']
        ) {
            return hs_api_responce('408', '平台id错误!');
        }

        return true;
    }

    /**
     * 验签
     *
     * @return $this|bool
     */
    public function checkSign() {
        return $this->oa_class->checkSign($this->param);
    }

    /**
     * 验证加密方式
     *
     * @return $this|bool
     */
    public function checkSignType() {
        if (!isset($this->param['sign_type'])||empty($this->param['sign_type'])) {
            return hs_api_responce('403', '签名方式错误');
        }
        if (!isset($this->oa_class->oa_conf) || !isset($this->oa_class->oa_conf['SIGN_TYPE'])
            || $this->oa_class->oa_conf['SIGN_TYPE'] != $this->param['sign_type']
        ) {
            return true;
        }
    }

    /**
     * 时间簇验证
     *
     * @return $this|bool
     */
    public function checkTimestamp() {
        if (!isset($this->param['timestamp'])||empty($this->param['timestamp'])) {
            return hs_api_responce('402', '请求时间错误');
        }
        if ($this->param['timestamp'] + 10 < time()) {
            return hs_api_responce('402', '请求时间超时');
        }

        return true;
    }
}

<?php
/**
 * Oacallback.php UTF-8
 *
 *
 * @date    : 2017/6/16 10:08
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : guxiannong <gxn@huosdk.com>
 * @version : HUOOA 1.0
 */
namespace app\oa\controller;
class Oacallback extends Oa {
    function _initialize() {
        parent::_initialize();

    }

    /**
     * 检测func参数
     *
     * @return $this
     */
    public function checkFunc() {
        \think\Log::write($this->param,'error');
        if (empty($this->param['func'])) {
            return hs_api_responce('409', 'func参数错误');
        }
    }

    public function index() {
        $this->checkFunc();
        if ($this->param['func'] == 'GM_FOSTER' || $this->param['func'] == 'GM_FIRST') {
            $this->param['func'] = 'OaFirstFoster';
        }
        if ($this->param['func'] == 'LOCK_USER') {
            $this->param['func'] = 'OaLogin';
        }
        self::init($this->param['func']);
    }

    public static function init($payway) {
        $_class = false !== strpos($payway, '\\') ? $payway : '\\app\\oa\\controller\\'.ucwords($payway);

        // 记录初始化信息
        return new $_class();
    }


}

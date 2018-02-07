<?php
/**
 * config.php UTF-8
 * OA对接配置文件
 *
 * @date    : 2017年5月19日上午9:51:48
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : guxiannong <gxn@huosdk.com>
 * @version : 1.0
 *
 */
$_oa_config= ROOT_SITE_PATH.'conf/oa.php';
if(file_exists($_oa_config)){
    $oa_config=include $_oa_config;
}else{
    $oa_config=array();
}
return $oa_config;


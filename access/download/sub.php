<?php
include "common/Sub.php";
/*
 * 需要配置三个参数
 */
$prj_id = '165';  /* 编号 */
$downurl = 'http://down.xiguamei.com/download/';  /* 下载地址 IOS使用 */
$iparr = array(
    '127.0.0.1',
    '123.56.231.14'
);  /* 限制IP */
$cnt = 0;
$root_path = __DIR__.DIRECTORY_SEPARATOR.'sdkgame'.DIRECTORY_SEPARATOR;
$downurl = $downurl.'/sdkgame/';
while (1) {
    $class = new Sub($root_path, $prj_id, $downurl, $iparr);
    $return = $class->subPack();
    if (-2 != $return || 3 == $cnt || strlen($return) > 10) {
        break;
    }
    $cnt++;
}
echo base64_encode($return);
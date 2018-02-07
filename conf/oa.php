<?php
/* OA平台提供的对接参数 */
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
return array(
    "PLAT_ID"           => "1",/* 平台ID */
    "PLAT_SECURE_KEY"   => "f875364690581668449d4cf0aeb60560",/* 平台的秘钥 */
    "METHOD"            => "POST",/* 提交方式 */
    "SIGN_TYPE"         => "MD5",/* 验证方式 */
    "OA_HOST"           => "http://127.0.0.1:8000/",/* 对接服务器 */
    "MEM_REG_URL"       => 'v1/api/user/reg',/* 用户注册 */
    "MEM_LOGIN_URL"     => 'v1/api/user/login',/* 用户登录 */
    "MEM_UPINFO_URL"    => 'v1/api/user/uproleinfo',/* 用户上传角色 */
    "MEM_PAY_URL"       => 'v1/api/user/pay',/* 用户充值 */
    "MEM_UPDATE_URL"    => 'v1/api/user/update',/* 用户修改归属*/
    "GAME_ADD_URL"      => 'v1/api/game/add',/* 添加游戏 */
    "GAME_UPDATE_URL"   => 'v1/api/game/update',/* 修改游戏 */
    "GAME_DELETE_URL"   => 'v1/api/game/delete',/* 删除游戏 */
    "GAME_RESTORE_URL"  => 'v1/api/game/restore',/* 还原已删除游戏 */
    "SERVER_ADD_URL"    => 'v1/api/server/add',/* 添加游戏区服 */
    "SERVER_UPDATE_URL" => 'v1/api/server/update',/* 修改游戏区服 */
    "GM_FIRST_URL"      => 'v1/api/gm/callback',/* 首充回调 */
    "GM_FOSTER_URL"     => 'v1/api/gm/callback',/* 扶植回调 */
    "GET_WEBINAR_URL"     => 'v1/api/webinar/get',/* 获取直播间 */
);


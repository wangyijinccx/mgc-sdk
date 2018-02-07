<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
return [
    /* 第一次请求接口 */
    ':version/install$'              => 'player/:version.System/install', /* 第一次请求接口  */
    /* 初始化接口 */
    ':version/system/startup'        => 'player/:version.System/open', /* 初始化接口  */
    /* 公告信息 */
    ':version/system/notice'         => 'player/:version.System/notice', /* 公告信息  */
    'agent/agentoareceive'           => 'agent/Agentoareceive/agentOaChange', /* agentoa接收处理  */
    /* 短信 */
    ':version/sms/send'              => 'api/:version.Sms/send', /* 发送手机短信  */
    /*玩家接口*/
    ':version/user/registerone'      => 'player/:version.Userreg/regOne', /* 一键注册  */
    ':version/user/register'         => 'player/:version.Userreg/register', /* 普通注册  */
    ':version/user/registermobile'   => 'player/:version.Userreg/regMobile', /* 手机注册  */
    ':version/user/login'            => 'player/:version.Userlogin/login', /* 普通登陆  */
    ':version/user/loginmobile'    => 'player/:version.Userlogin/loginMobile', /* 手机登陆  */
    ':version/user/loginoauth'     => 'player/:version.Userlogin/loginOauth', /* 第三方登陆  */
    ':version/user/logout'         => 'player/:version.Userlogin/logout', /* 登出  */
    ':version/user/status'         => 'player/:version.Userlogin/status', /* 检查用户状态接口  */
    ':version/web/indentify/index' => 'wap/:version.Indentify/index', /* 实名信息填写 20170510 ou */
    /*玩家游戏数据*/
    ':version/user/uproleinfo'     => 'player/:version.Userrole/set', /* 上传角色信息  */
    ':version/user/webinar'     => 'player/:version.Userrole/getWebinar', /* 获取直播间信息  */
    ':version/user/couples'     => 'player/:version.Userrole/couples', /* 上传连麦信息  */
    ':version/user/connect'     => 'player/:version.Userrole/connect', /* 连麦  */

    /*oa接口*/
    'oa/oalogin'                   => 'oa/oalogin/login', /* 上传角色信息  */
    'oa/callback'                  => 'oa/Oacallback/index', /* OA访问处理  */

    /*支付*/
    ':version/pay/sdkpay'          => 'pay/Sdkpay/index', /* 游戏预下单  */
    ':version/get/goods'           => 'pay/Sdkpay/index', /* 游戏预下单  */
    ':version/pay/preorder'        => 'pay/Sdkpay/pay', /* 玩家支付  */
    'pay/preorder'                 => 'pay/Sdkpay/pay', /* 玩家支付  */
    'get/preorder'                 => 'pay/Sdkpay/pay', /* 玩家支付  */
    ':version/get/preorder'        => 'pay/Sdkpay/pay', /* 玩家支付  */
    'txthinkpay/notify'            => 'Pay/Thirdpay/notifyurl', /* 支付宝支付回调地址  */
    'alipay/notify'                => 'Pay/alipay/notifyurl', /* 支付宝支付回调地址  */
    'alipay/return'                => 'Pay/alipay/returnurl', /* 支付宝支付通知地址  */
    'alipay/showurl'               => 'Pay/alipay/showurl',
    'alipay/show'                  => 'Pay/alipay/showurl', /* 支付宝支付通知地址  */
    'alipay/outurl'                  => 'Pay/alipay/outweburl', /* 支付宝支付通知地址  */
    'now/notify'                     => 'Pay/Nowpay/notifyurl', /* 现在支付通知地址  */
    'now/h5notify'                   => 'Pay/Nowpay/notifyh5url', /* 现在支付通知地址  */
    'now/return'                     => 'Pay/Nowpay/returnurl', /* 现在支付通知地址  */
    'now/gotoweixin'                 => 'Pay/Nowpay/gotoweixin', /* 现在支付跳转微信  */
    'now/check'                      => 'Pay/Nowpay/checkurl', /* 现在支付校验订单是否OK */
    'spay/notify'                    => 'Pay/Spay/notifyurl', /* 威富通支付通知地址  */
    'alipay/submit'                  => 'Pay/alipay/submit', /* 支付宝提交支付地址  */
    'payeco/notify'                  => 'Pay/Payeco/notifyurl', /* 易联支付通知地址  */
    'heepay/notify'                  => 'Pay/Heepay/notifyurl', /* 汇付宝通知地址  */
    ':version/pay/queryorder'        => 'player/:version.Order/queryOrder', /* 查询支付结果  */
    ':version/apppay/preorder'       => 'pay/Applepay/preorder', /* 非 web预下单  */
    ':version/apppay/checkorder'     => 'pay/Applepay/checkorder', /* 苹果原生支付验单  */
    'unionpay/notify'                => 'Pay/unionpay/notifyurl', /* 银联支付回调地址  */
    'unionpay/return'                => 'Pay/unionpay/returnurl', /* 银联支付通知地址  */
    'zwxpay/notify'                  => 'Pay/zwxpay/notifyurl', /* 梓微兴支付回调地址  */
    'zwxpay/return'                  => 'Pay/zwxpay/returnurl', /* 梓微兴支付通知地址  */
    'tlsdk/notify'                   => 'Pay/TlSdk/notify',     /* 太浪SDK通知地址 */
    'tlsdk/test'                   => 'Pay/TlSdk/testpay',     /* 太浪SDK通知地址 */
    /* 浮点 */
    ':version/web/user/index'        => 'wap/:version.User/index', /* 用户中心  */
    ':version/web/bbs/index'         => 'wap/:version.Bbs/index', /* 论坛  */
    ':version/web/gift/index'        => 'wap/:version.Gift/index', /* 礼包中心  */
    ':version/web/help/index'        => 'wap/:version.Help/index', /* 客服中心  */
    ':version/web/forgetpwd/index'   => 'wap/:version.Forgetpwd/index', /* 找回密码  */
    ':version/web/code/index'        => 'wap/:version.Code/index', /* 填写邀请码 20170105 wuyonghong */
    ':version/web/strategy/index'    => 'wap/:version.Strategy/index', /* 打开浮点-攻略 20170310 wuyonghong */
    ':version/web/h5/index'          => 'wap/:version.H5/index', /* 打开浮点-跳转h5 20170310 wuyonghong */
    /* CP 校验 */
    'cp/user/check'                  => 'cp/v7.Cp/check', /* CP用户校验  */
    ':version/cp/user/check'         => 'cp/:version.Cp/check', /* CP用户校验  */
    ':version/cp/payback/test$'      => 'cp/:version.Payback/notify', /* 支付回调测试  */
    'cp/payback/test$'               => 'cp/v7.Payback/notify', /* 支付回调测试  */
    /* 下载地址 */
    '[down]'                         => [
        'downid/:downid/gameid/:gameid$' => ['api/v7.Gamedown/down', [], ['downid' => '\d+', 'gameid' => '\d+']],
        'downid/:downid$'                => ['api/v7.Gamedown/down', [], ['downid' => '\d+']],
        'gameid/:gameid$'                => ['api/v7.Gamedown/down', [], ['gameid' => '\d+']],
        '__miss__'                       => 'api/v7.Gamedown/down',
    ],
    'downid/:downid/gameid/:gameid$' => 'api/v7.Gamedown/down', /* CP用户校验  */
    'downid/:downid$'                => 'api/v7.Gamedown/down', /* CP用户校验  */
    'gameid/:gameid$'                => 'api/v7.Gamedown/down', /* CP用户校验  */
];

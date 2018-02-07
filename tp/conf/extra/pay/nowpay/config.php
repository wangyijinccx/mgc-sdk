<?php
/**
 * config.php UTF-8
 * 威付通配置文件
 *
 * @date    : 2016年6月19日下午3:58:48
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@1tsdk.com>
 * @version : 1.0
 *
 */
return array(
    "appId"                   => "149241831659845",//商户的应用ID
    "secure_key"              => "eGcb6epHgjfuBb1KnVL2VSNcNahSTnX1",//商户的秘钥
    "timezone"                => "Asia/Shanghai",//时间时区
    "trade_time_out"          => "3600",
    "front_notify_url"        => "",
    "back_notify_url"         => "",
    "TRADE_URL"               => "https://pay.ipaynow.cn",
    "QUERY_URL"               => "https://pay.ipaynow.cn",
    "TRADE_FUNCODE"           => "WP001",
    "QUERY_FUNCODE"           => "MQ001",
    "NOTIFY_FUNCODE"          => "N001",
    "FRONT_NOTIFY_FUNCODE"    => "N002",
    "TRADE_TYPE"              => "01",
    "TRADE_CURRENCYTYPE"      => "156",
    "TRADE_CHARSET"           => "UTF-8",
    "TRADE_DEVICE_TYPE"       => "06",
    "TRADE_SIGN_TYPE"         => "MD5",
    "TRADE_QSTRING_EQUAL"     => "=",
    "TRADE_QSTRING_SPLIT"     => "&",
    "TRADE_FUNCODE_KEY"       => "funcode",
    "TRADE_DEVICETYPE_KEY"    => "deviceType",
    "TRADE_SIGNTYPE_KEY"      => "mhtSignType",
    "TRADE_SIGNATURE_KEY"     => "mhtSignature",
    "SIGNATURE_KEY"           => "signature",
    "SIGNTYPE_KEY"            => "signType",
    "VERIFY_HTTPS_CERT"       => false,
    "TRADE_PAYCHANNELTYPE"    => "13",
    "H5_TRADE_PAYCHANNELTYPE" => "1301",
    "H5_appId"                => "149241843283426",//商户的应用ID
    "H5_secure_key"           => "Ou8OZvVz1Ddz7Kh8AOxk3YoIx3pLSC8C",//商户的秘钥
);

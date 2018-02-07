<?php
    require_once 'Config.php';
/**
 *
 * 类名:网络通信类
 * 功能:发送并接受HTTP消息
 * 版本:1.0
 * 日期:2014-6-14
 * 作者:中怡同创技术团队
 * 版权:中怡同创技术团队
 * 说明:以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己的需要，按照技术文档编写，并非一定要使用该代码。该代码仅供参考
 */
class Net {
    /**
     * 发送信息
     *
     * @param type $req_content 请求字符串
     * @param type $url         请求地址
     *
     * @return type 应答消息
     */
    static function sendMessage($req_content, $url) {
        if (function_exists("curl_init")) {
            $curl = curl_init();
            $option = array(
                CURLOPT_POST           => 1,
                CURLOPT_POSTFIELDS     => $req_content,
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HEADER         => 0,
                CURLOPT_SSL_VERIFYPEER => Config::VERIFY_HTTPS_CERT,
                CURLOPT_SSL_VERIFYHOST => Config::VERIFY_HTTPS_CERT
            );
            curl_setopt_array($curl, $option);
            $resp_data = curl_exec($curl);
            if ($resp_data == false) {
                curl_close($curl);
            } else {
                curl_close($curl);

                return $resp_data;
            }
        }
    }
}
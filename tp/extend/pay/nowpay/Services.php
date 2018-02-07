<?php
require_once 'Core.php';
require_once 'Config.php';
require_once 'Net.php';
if (function_exists("date_default_timezone_set")) {
    date_default_timezone_set('Asia/Shanghai');
}

class Services {
    public static function trade(Array $params) {
        return Core::createLinkString($params, false, true);
    }

    /*public static function query(Array $params,Array &$resp) {
        $req_str=self::buildReq($params);
        $resp_str=Net::sendMessage($req_str, Config::QUERY_URL);
        return self::verifyResponse($resp_str, $resp);
    }*/
    public static function buildSignature(Array $params) {
        $filteredReq = Core::paraFilter($params);
        return Core::buildSignature($filteredReq, $params);
    }

    private static function buildReq(Array $params) {
        return Core::createLinkString($params, false, true);
    }

    public static function verifySignature($para) {
        $respSignature = $para[Config::SIGNATURE_KEY];
        $filteredReq = Core::paraFilter($para);
        $signature = Core::buildSignature($filteredReq, $para);
        if ($respSignature != "" && $respSignature == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public static function verifyResponse($resp_str, &$resp) {
        if ($resp_str != "") {
            parse_str($resp_str, $para);
            $signIsValid = self::verifySignature($para);
            $resp = $para;
            if ($signIsValid) {
                return true;
            } else {
                return false;
            }
        }
    }
}
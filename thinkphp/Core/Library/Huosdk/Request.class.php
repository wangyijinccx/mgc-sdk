<?php
/**
 * Request.php UTF-8
 * 各个请求函数
 *
 * @date    : 2016年11月16日下午2:45:36
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2016年11月16日下午2:45:36
 */
/**
 * Rsaauth.class.php UTF-8
 * 对称 非对称加密 请求与返回
 *
 * @date    : 2016年11月9日下午11:46:45
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : wuyonghong <wyh@huosdk.com>
 * @version : HUOSDK 7.0
 * @modified: 2016年11月9日下午11:46:45
 */
namespace Huosdk;
/* use think\Log; */
if (!function_exists('daddslashes')) {
    function daddslashes($string, $force = 0) {
        return uc_addslashes($string, $force);
    }
}
if (!function_exists('dhtmlspecialchars')) {
    function dhtmlspecialchars($string, $flags = null) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = dhtmlspecialchars($val, $flags);
            }
        } else {
            if ($flags === null) {
                $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
                if (strpos($string, '&amp;#') !== false) {
                    $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
                }
            } else {
                if (PHP_VERSION < '5.4.0') {
                    $string = htmlspecialchars($string, $flags);
                } else {
                    if (strtolower(CHARSET) == 'utf-8') {
                        $charset = 'UTF-8';
                    } else {
                        $charset = 'ISO-8859-1';
                    }
                    $string = htmlspecialchars($string, $flags, $charset);
                }
            }
        }

        return $string;
    }
}
if (!function_exists('fsocketopen')) {
    function fsocketopen($hostname, $port = 80, &$errno, &$errstr, $timeout = 15) {
        $fp = '';
        if (function_exists('fsockopen')) {
            $fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
        } elseif (function_exists('pfsockopen')) {
            $fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
        } elseif (function_exists('stream_socket_client')) {
            $fp = @stream_socket_client($hostname.':'.$port, $errno, $errstr, $timeout);
        }

        return $fp;
    }
}

class Request {
    /**
     * CP 回调请求函数
     *
     * @param $url    string 请求地址与端口
     * @param $params string post数据
     *
     * @return 请求结果
     */
    public static function cpPayback($url, $params) {
        $curl = curl_init(); //初始化curl
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_POST, 1); // post传输数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);// post传输数据
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $header = array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $responseText = curl_exec($curl);
        curl_close($curl);
        $rs = strtoupper($responseText);
        $result = 0;
        if ('SUCCESS' == $rs) {
            $result = 1;
        } else {
            $result = 0;
        }

        return $result;
    }

    /**
     * CP 回调请求函数
     *
     * @param $url    string 请求地址与端口
     * @param $params string post数据
     *
     * @return 请求结果
     */
    public static function httpJsonpost($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
                   'Content-Type: application/json; charset=utf-8',
                   'Content-Length: '.strlen($params))
        );
        //ob_start();
        $return_content = curl_exec($ch);
        //$return_content = ob_get_contents();
        //ob_end_clean();
        return $return_content;
    }

    /**
     * 添加游戏
     *
     * @param array $game_data
     *
     * @return array|bool
     */
    public static function add_oa_game($game_data = array()) {
        if (self::canOa()) {
            if (!isset($game_data['app_id']) || !isset($game_data['name']) || !isset($game_data['classify'])
                || !isset($game_data['gameflag'])
                || !isset($game_data['create_time'])
                || !isset($game_data['status'])
                || !isset($game_data['pinyin'])
                || !isset($game_data['initial'])
                || !isset($game_data['version'])
            ) {
                return array(
                    'code' => 403,
                    'msg'  => '参数错误',
                    'data' => $game_data
                );
            }
            $_param = [];
            $_param['app_id'] = $game_data['app_id'];
            $_param['gamename'] = $game_data['name'];
            $_param['classify'] = $game_data['classify'];
            $_param['gameflag'] = $game_data['gameflag'];
            $_param['create_time'] = $game_data['create_time'];
            $_param['status'] = $game_data['status'];
            $_param['pinyin'] = $game_data['pinyin'];
            $_param['initial'] = $game_data['initial'];
            $_param['game_version'] = $game_data['version'];
            if (isset($game_data['standard_mem_cnt'])) {
                $_param['target_cnt'] = $game_data['standard_mem_cnt'];
            }
            if (isset($game_data['target_cnt'])) {
                $_param['target_cnt'] = $game_data['target_cnt'];
            }
            if (isset($game_data['target_level'])) {
                $_param['target_level'] = $game_data['target_level'];
            }
            if (isset($game_data['standard_level'])) {
                $_param['target_level'] = $game_data['standard_level'];
            }
            $_return_data = self::sendToOA('GAME_ADD', $_param);

            return array(
                'code'  => 200,
                'msg'   => '处理完成',
                'data'  => $_return_data,
                'param' => $_param,
            );
        } else {
            return true;
        }
    }

    public static function add_oa_order($param = array()) {
        if (self::canOa()) {
            $func = 'MEM_PAY';
            if (empty($param['create_time'])) {
                return array(
                    'code' => 401,
                    'msg'  => 'create_time参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['time'] = $param['create_time'];
            }
            if (empty($param['app_id'])) {
                return array(
                    'code' => 402,
                    'msg'  => 'app_id参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['app_id'] = $param['app_id'];
            }
            if (empty($param['username'])) {
                return array(
                    'code' => 403,
                    'msg'  => 'username参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['username'] = $param['username'];
            }
            if (empty($param['agentname'])) {
                return array(
                    'code' => 403,
                    'msg'  => 'username参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['agentname'] = $param['agentname'];
            }
            if (empty($param['pay_ip'])) {
                return array(
                    'code' => 404,
                    'msg'  => 'pay_ip参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['ip'] = $param['pay_ip'];
            }
            if (empty($param['imei'])) {
                $_query_param['device_id'] = '';
            } else {
                $_query_param['device_id'] = $param['imei'];
            }
            if (empty($param['from'])) {
                return array(
                    'code' => 405,
                    'msg'  => 'from参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['from'] = $param['from'];
            }
            if (empty($param['order_id'])) {
                return array(
                    'code' => 406,
                    'msg'  => 'order_id参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['order_id'] = $param['order_id'];
            }
            if (empty($param['payway'])) {
                return array(
                    'code' => 407,
                    'msg'  => 'payway参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['payway'] = $param['payway'];
            }
            if (empty($param['amount'])) {
                return array(
                    'code' => 408,
                    'msg'  => 'amount参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['amount'] = $param['amount'];
            }
            if (empty($param['status'])) {
                return array(
                    'code' => 409,
                    'msg'  => 'status参数错误',
                    'data' => $param
                );
            } else {
                $_query_param['status'] = $param['status'];
            }
            if (empty($param['real_amount'])) {
                $_query_param['real_amount'] = '';
            } else {
                $_query_param['real_amount'] = $param['real_amount'];
            }
            if (empty($param['rebate_cnt'])) {
                $_query_param['rebate_cnt'] = 0;
            } else {
                $_query_param['rebate_cnt'] = $param['rebate_cnt'];
            }
            if (empty($param['gm_cnt'])) {
                $_query_param['gm_cnt'] = '';
            } else {
                $_query_param['gm_cnt'] = $param['gm_cnt'];
            }
            if (empty($param['role_level'])) {
                $_query_param['role_level'] = '';
            } else {
                $_query_param['role_level'] = $param['role_level'];
            }
            if (empty($param['role_name'])) {
                $_query_param['role_name'] = '';
            } else {
                $_query_param['role_name'] = $param['role_name'];
            }
            if (empty($param['role_id'])) {
                $_query_param['role_id'] = '';
            } else {
                $_query_param['role_id'] = $param['role_id'];
            }
            if (empty($param['server_id'])) {
                $_query_param['server_id'] = '';
            } else {
                $_query_param['server_id'] = $param['server_id'];
            }
            if (empty($param['server_name'])) {
                $_query_param['server_name'] = '';
            } else {
                $_query_param['server_name'] = $param['server_name'];
            }
            if (empty($param['userua'])) {
                $_query_param['userua'] = '';
            } else {
                $_query_param['userua'] = $param['userua'];
            }
            $_return_data = self::sendToOA($func, $_query_param);

            return array(
                'code'  => 200,
                'msg'   => '处理完成',
                'data'  => $_return_data,
                'param' => $_query_param,
            );
        } else {
            return true;
        }
    }


    /**
     * 判断是否能使用oa
     *
     * @return bool
     */
    public static function canOa() {
        if (C('G_OA_EN')) {
            return true;
        }

        return false;
    }

    /**
     * 与oa通信
     *
     * @param string $type 地址url的标识不待_URL
     * @param array  $param
     *
     * @return bool|请求结果
     */
    public static function sendToOA($type = '', $param = []) {
        if (self::canOa()) {
            $_oa_config = SITE_PATH."conf/oa.php";
            if (file_exists($_oa_config)) {
                $oa_config = include $_oa_config;
            } else {
                return false;
            }
            if (!isset($oa_config['OA_HOST']) || !isset($oa_config['PLAT_ID']) || !isset($oa_config['SIGN_TYPE'])
                || !isset($oa_config['PLAT_SECURE_KEY'])
            ) {
                return false;
            }
            $_url_code = $type.'_URL';
            $_type_url = $oa_config[$_url_code];
            if (empty($_type_url)) {
                return false;
            }
            $_url = $oa_config['OA_HOST'].$oa_config[$_url_code];
            $_param = $param;
            $_param['plat_id'] = $oa_config['PLAT_ID'];
            $_param['timestamp'] = time();
            $_param['sign_type'] = $oa_config['SIGN_TYPE'];
            $_query_str = self::build_param($_param, $oa_config['PLAT_SECURE_KEY']);
            $_cookie = '';
            $_timeout = 0;
            $_return_data = self::asyncRequst(
                $_url, $_query_str, $_cookie, $_timeout
            );
            return $_return_data;
        } else {
            return true;
        }
    }

    /**
     * 构建请求参数
     *
     * @param array  $param
     * @param string $key
     *
     * @return string
     */
    public static function build_param(array $param, $key = '') {
        $_param = $param;
        $_param['sign'] = self::getSign($param, $key);

        return self::createLinkstring($_param);
    }

    /**
     * 异步请求
     *
     * @param $url     string 请求地址与端口
     * @param $post    string post数据
     * @param $cookie  string cookie数据
     * @param $timeout int 请求超时时间
     *
     * @return 请求结果
     */
    public static function asyncRequst($url, $post, $cookie, $timeout) {
        return self::socketRequest($url, 0, $post, $cookie, true, '', $timeout, false);
    }

    /**
     * 生成签名
     *
     * @param        $param
     * @param string $key
     *
     * @return string
     */
    public static function getSign($param, $key = '') {
        $_param = self::argSort($param);
        $_str = self::createLinkstring($_param);
        $_sign = md5($_str.'&key='.$key);

        return $_sign;
    }

    /**
     * 创建参数字符串
     *
     * @param array $para
     *
     * @return string
     */
    public static function createLinkstring(array $para) {
        $arg = "";
        while (list($key, $val) = each($para)) {
            $arg .= $key."=".urlencode($val)."&";
        }
        // 去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        // 如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * socket 请求函数
     *
     * @param             $url        string 请求地址与端口
     * @param             $limit      int 读取长度
     * @param             $post       string post数据
     * @param             $cookie     string cookie数据
     * @param             $bysocket   bool 是否使用socket
     * @param bool|string $ip         bool 请求连接的IP 空则取$host
     * @param             $timeout    int 请求超时时间
     * @param             $block      bool 是否阻塞
     * @param             $encodetype string 是否启用urldecode
     *
     * @return 请求结果
     */
    public static function socketRequest(
        $url, $limit = 0, $post = '', $cookie = '', $bysocket = false, $ip = '', $timeout = 15, $block = true,
        $encodetype = 'URLENCODE'
    ) {
        $return = '';
        $matches = parse_url($url);
        $scheme = $matches['scheme'];
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].((isset($matches['query']) && $matches['query']) ? '?'
                                                                                                       .$matches['query']
                : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;
        if ($post) {
            $out = "POST $path HTTP/1.0\r\n";
            $header = "Accept: */*\r\n";
            $header .= "Accept-Language: zh-cn\r\n";
            $boundary = $encodetype == 'URLENCODE' ? '' : ';'.substr($post, 0, trim(strpos($post, "\n")));
            $header .= $encodetype == 'URLENCODE' ? "Content-Type: application/x-www-form-urlencoded\r\n"
                : "Content-Type: multipart/form-data$boundary\r\n";
            $header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $header .= "Host: $host:$port\r\n";
            $header .= 'Content-Length: '.strlen($post)."\r\n";
            $header .= "Connection: Close\r\n";
            $header .= "Cache-Control: no-cache\r\n";
            $header .= "Cookie: $cookie\r\n\r\n";
            $out .= $header.$post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $header = "Accept: */*\r\n";
            $header .= "Accept-Language: zh-cn\r\n";
            $header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $header .= "Host: $host:$port\r\n";
            $header .= "Connection: Close\r\n";
            $header .= "Cookie: $cookie\r\n\r\n";
            $out .= $header;
        }
        $fpflag = 0;
        if (!$bysocket || !$fp = @fsocketopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout)) {
            $context = array('http' => array('method'  => $post ? 'POST' : 'GET', 'header' => $header,
                                             'content' => $post, 'timeout' => $timeout
            )
            );
            $context = stream_context_create($context);
            $fp = @fopen($scheme.'://'.($ip ? $ip : $host).':'.$port.$path, 'b', false, $context);
            $fpflag = 1;
        }
        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if (!$status['timed_out']) {
                while (!feof($fp) && !$fpflag) {
                    if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                        break;
                    }
                }
                $stop = false;
                while (!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if ($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);

            return $return;
        }
    }

    /**
     * 排序数组
     *
     * @param array $para
     *
     * @return array
     */
    public static function argSort(array $para) {
        ksort($para);
        reset($para);

        return $para;
    }

    /**
     * 修改游戏
     *
     * @param array $update_data
     *
     * @return array|bool
     */
    public static function update_oa_game($update_data = array()) {
        if (self::canOa()) {
            if (!isset($update_data['app_id']) || !$update_data['app_id'] || !is_numeric($update_data['app_id'])) {
                return array(
                    'code' => 403,
                    'msg'  => '参数错误',
                    'data' => $update_data,
                );
            }
            $_up_info = array();
            if (isset($update_data['update_time'])) {
                $_up_info['update_time'] = $update_data['update_time'];
            }
            if (isset($update_data['status'])) {
                $_up_info['status'] = $update_data['status'];
            }
            if (isset($update_data['pinyin'])) {
                $_up_info['pinyin'] = $update_data['pinyin'];
            }
            if (isset($update_data['initial'])) {
                $_up_info['initial'] = $update_data['initial'];
            }
            if (isset($update_data['teststatus'])) {
                $_up_info['teststatus'] = $update_data['teststatus'];
            }
            /* 这里是mobile_icon 手机游戏图标 */
            if (isset($update_data['icon'])) {
                $_up_info['icon'] = $update_data['icon'];
            } else {
                if (isset($update_data['mobile_icon'])) {
                    $_up_info['icon'] = $update_data['mobile_icon'];
                }
            }
            if (isset($update_data['target_cnt'])) {
                $_up_info['target_cnt'] = $update_data['target_cnt'];
            }
            if (isset($update_data['target_level'])) {
                $_up_info['target_level'] = $update_data['target_level'];
            }
            if (isset($update_data['run_time'])) {
                $_up_info['run_time'] = $update_data['run_time'];
            }
            if (isset($update_data['parent_id'])) {
                $_up_info['parent_id'] = $update_data['parent_id'];
            }
            /* 这里是name */
            if (isset($update_data['name'])) {
                $_up_info['gamename'] = $update_data['name'];
            }
            if (isset($update_data['classify'])) {
                $_up_info['classify'] = $update_data['classify'];
            }
            if (isset($update_data['gameflag'])) {
                $_up_info['gameflag'] = $update_data['gameflag'];
            }
            if (isset($update_data['version'])) {
                $_up_info['game_version'] = $update_data['version'];
            }
            $_param = [];
            $_param['app_id'] = $update_data['app_id'];
            $_param['upinfo'] = json_encode($_up_info);
            $_return_data = self::sendToOA('GAME_UPDATE', $_param);

            return array(
                'code' => 200,
                'msg'  => '处理完成',
                'data' => $_return_data
            );
        } else {
            return true;
        }
    }

    /**
     * 删除游戏
     *
     * @param array $delete_data
     *
     * @return array|bool
     */
    public static function delete_oa_game($delete_data = array()) {
        if (self::canOa()) {
            if (!isset($delete_data['app_id']) || !$delete_data['app_id'] || !is_numeric($delete_data['app_id'])) {
                return array(
                    'code' => 403,
                    'msg'  => '参数错误',
                    'data' => $delete_data,
                );
            }
            $_param = [];
            $_param['app_id'] = $delete_data['app_id'];
            $_param['delete_time'] = (isset($delete_data['delete_time']) && $delete_data['delete_time'])
                ? $delete_data['delete_time'] : time();
            $_return_data = self::sendToOA('GAME_DELETE', $_param);

            return array(
                'code' => 200,
                'msg'  => '处理完成',
                'data' => $_return_data,
            );
        } else {
            return true;
        }

        return false;
    }

    /**
     * 还原已删除游戏
     *
     * @param array $restore_data
     *
     * @return array|bool
     */
    public static function restore_oa_game($restore_data = array()) {
        if (self::canOa()) {
            if (!isset($restore_data['app_id']) || !$restore_data['app_id'] || !is_numeric($restore_data['app_id'])) {
                return array(
                    'code' => 403,
                    'msg'  => '参数错误',
                    'data' => $restore_data,
                );
            }
            $_param = [];
            $_param['app_id'] = $restore_data['app_id'];
            $_param['restore_time'] = (isset($restore_data['restore_time']) && $restore_data['restore_time'])
                ? $restore_data['restore_time'] : time();
            $_return_data = self::sendToOA('GAME_RESTORE', $_param);

            return array(
                'code' => 200,
                'msg'  => '处理完成',
                'data' => $_return_data,
            );
        } else {
            return true;
        }

        return false;
    }

    /**
     * 添加游戏区服
     *
     * @param array $data
     *
     * @return array|bool
     */
    public static function add_oa_game_server($data = array()) {
        if (self::canOa()) {
            if (!isset($data['app_id']) || !$data['app_id'] || !isset($data['server_id']) || !$data['server_id']
                || !isset($data['ser_name'])
                || '' == $data['ser_name']
                || is_bool($data['ser_name'])
                || !isset($data['ser_code'])
                || !$data['ser_code']
                || !isset($data['create_time'])
                || !$data['create_time']
                || !is_numeric($data['app_id'])
            ) {
                return array(
                    'code' => 403,
                    'msg'  => '参数错误',
                    'data' => $data
                );
            }
            $_param = [];
            $_param['app_id'] = $data['app_id'];
            $_param['server_id'] = $data['server_id'];
            $_param['server_code'] = $data['ser_code'];
            $_param['server_name'] = $data['ser_name'];
            if (isset($data['ser_desc'])) {
                $_param['server_desc'] = $data['ser_desc'];
            }
            if (isset($data['status'])) {
                $_param['status'] = $data['status'];
            }
            if (isset($data['is_delete'])) {
                $_param['is_delete'] = $data['is_delete'];
            }
            if (isset($data['gamename'])) {
                $_param['gamename'] = $data['gamename'];
            }
            if (isset($data['start_time'])) {
                $_param['start_time'] = $data['start_time'];
            }
            $_param['create_time'] = $data['create_time'];
            $_return_data = self::sendToOA('SERVER_ADD', $_param);
            return array(
                'code' => 200,
                'msg'  => '处理完成',
                'data' => $_return_data,
            );
        } else {
            return true;
        }
    }

    /**
     * 更新游戏区服信息
     *
     * @param array $data
     *
     * @return array|bool
     */
    public static function update_oa_game_server($data = array()) {
        if (self::canOa()) {
            if (!isset($data['app_id']) || !$data['app_id']
                || !isset($data['ser_name'])
                || !$data['ser_name']
                || !isset($data['ser_code'])
                || !$data['ser_code']
                || !isset($data['update_time'])
                || !$data['update_time']
                || !is_numeric($data['app_id'])
            ) {
                return array(
                    'code' => 403,
                    'msg'  => '参数错误',
                    'data' => $data
                );
            }
            $_param = [];
            $_param['app_id'] = $data['app_id'];
            $_param['server_id'] = $data['server_id'];
            $_param['update_time'] = $data['update_time'];
            $_param['server_code'] = $data['ser_code'];
            $_param['server_name'] = $data['ser_name'];
            if (isset($data['ser_desc'])) {
                $_param['server_desc'] = $data['ser_desc'];
            }
            if (isset($data['gamename'])) {
                $_param['gamename'] = $data['gamename'];
            }
            if (isset($data['status'])) {
                $_param['status'] = $data['status'];
            }
            if (isset($data['is_delete'])) {
                $_param['is_delete'] = $data['is_delete'];
            }
            if (isset($data['start_time'])) {
                $_param['start_time'] = $data['start_time'];
            }
            $_return_data = self::sendToOA('SERVER_UPDATE', $_param);

            return array(
                'code' => 200,
                'msg'  => '处理完成',
                'data' => $_return_data,
            );
        } else {
            return true;
        }
    }

    /**
     * 更改玩家归属
     *
     * @param $username
     * @param $agentname
     *
     * @return array|bool
     */
    public static function mem_update_agentname($username, $agentname) {
        if (self::canOa()) {
            $_param = [];
            $_param['username'] = $username;
            $_param['agentname'] = $agentname;
            $_return_data = self::sendToOA('MEM_UPDATE', $_param);

            return array(
                'code' => 200,
                'msg'  => '处理完成',
                'data' => $_return_data,
            );
        } else {
            return true;
        }
    }

    /**
     * 扶植首充回调到oa
     *
     * @param int    $id
     * @param int    $game_id
     * @param int    $status
     * @param int    $type
     * @param string $reason
     *
     * @return array|bool
     */
    public static function oaGmCallBack($id = 0, $game_id = 0, $status = 0, $type = 0, $reason = '') {
        if (self::canOa()) {
            if (empty($id) || empty($game_id) || empty($status) || empty($type)) {
                return false;
            }
            $_param = [];
            $_param['id'] = $id;
            $_param['status'] = $status;
            $_param['reason'] = $reason;
            $_param['type_id'] = $type;
            $_param['app_id'] = $game_id;
            $url_code = '';
            switch ($type) {
                case 1:
                    $url_code = 'GM_FIRST';
                    break;
                case 2:
                    $url_code = 'GM_FOSTER';
                    break;
            }
            if ($url_code) {
                $_return_data = self::sendToOA($url_code, $_param);

                return array(
                    'code' => 200,
                    'msg'  => '处理完成',
                    'data' => $_return_data,
                );
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * 自定义错误处理
     *
     * @param 输出的文件  $msg
     * @param string $level
     *
     * @internal param 输出的文件 $msg
     */
    private function _error($msg, $level = 'error') {
        $_info = 'request\Request Error:'.$msg;
        /* Log::record($_info, $level); */
    }
}
<?php
define("IN_WAF_PLATFORM", true);

require_once("config.php");
require_once("functions.php");

function load_waf_record($ip, $time) {
    $code = 0;
    $msg = "";
    $data = array();
    if ($time === 'all') {
        $tmp = scandir(DATA_PATH);
        $logname = array();
        foreach ($tmp as $name) {
            $t = LOG_NAME;
            if (preg_match("/{$t}_[0-9]{10}/", $name)) {
                $logname[] = $name;
            };
        }
        $tmp = $logname;
        foreach ($tmp as $file) {
            $logFiles[] = DATA_PATH.'/'.$file;
        }
    }
    else {
        $logFiles = array(DATA_PATH.'/'.LOG_NAME.'_'.$time);
    }
    
    $ips = array();
    
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile)) {
            $info = file($logFile);
            if ($info !== false) {
                foreach ($info as $item) {
                    // 防止xss
                    $tmp = json_decode($item, JSON_UNESCAPED_UNICODE);
                    if ($ip !== 'all' && $tmp['user_IP'] !== $ip) {
                        continue;
                    }
                    $tmp['user_IP'] = stripStr($tmp['user_IP']);
                    $tmp['request_method'] = stripStr($tmp['request_method']);
                    $tmp['script_name'] = stripStr($tmp['script_name']);
                    $tmp['request_time'] = stripStr($tmp['request_time']);
                    $tmp['headers_data'] = json_encode(stripArr($tmp['headers_data']), JSON_UNESCAPED_UNICODE);
                    $tmp['get_data'] = json_encode(stripArr($tmp['get_data']), JSON_UNESCAPED_UNICODE);
                    $tmp['post_data'] = json_encode(stripArr($tmp['post_data']), JSON_UNESCAPED_UNICODE);
                    $tmp['cookie_data'] = json_encode(stripArr($tmp['cookie_data']), JSON_UNESCAPED_UNICODE);

                    foreach ($tmp['files_data'] as $key => $value) {
                        $tmp[$key] = stripArr($tmp[$key]);
                    }
                    $tmp['files_data'] = json_encode($tmp['files_data'], JSON_UNESCAPED_UNICODE);
                    
                    $tmp['wafed_result'] = stripStr($tmp['wafed_result']);

                    $data[] = $tmp;

                    if (!in_array($tmp['user_IP'], $ips)) {
                        $ips[] = $tmp['user_IP'];
                    }
                }
            }
        }
    }
    $data = array_reverse($data);

    $count = count($data);

    $array = compact("code", "msg", "count", "data");

    // 写入ip
    sort($ips);
    
    $ipFile = DATA_PATH.'/'.IP_NAME;
    if (!file_exists($ipFile)) {
        touch($ipFile);
    }
    $res = '';
    foreach ($ips as $ip) {
        $res .= ($ip.PHP_EOL);
    }
    file_put_contents($ipFile, $res);
    
    return json_encode($array, JSON_UNESCAPED_UNICODE);
}

if (isset($_GET['cmd'])) {
    if ($_GET['cmd'] === 'load_waf_record') {
        $IP = isset($_GET['ip'])? $_GET['ip']: 'all';
        $TIME = isset($_GET['time'])? $_GET['time']: 'all';
        echo load_waf_record($IP, $TIME);
    }
}
?>
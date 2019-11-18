<?php
ignore_user_abort(true);
error_reporting(0);

define("IN_WAF_PLATFORM", true);

require_once("config.php");
require_once("functions.php");

// 忽略没有GET，POST和FILES参数的请求
if (IGNORE_REQUESTS_WITH_NO_PARAMS) {
    if (count($_GET)==0 && count($_POST)==0 && count($_FILES)==0) {
        return;
    }
}

$user_IP = isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR']: "unknown";
$request_method = isset($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD']: "unknown";
$script_name = isset($_SERVER['SCRIPT_NAME'])? $_SERVER['SCRIPT_NAME']: "unknown";
$request_time = isset($_SERVER['REQUEST_TIME'])? $_SERVER['REQUEST_TIME']: time();
$headers_data = getallheaders();

$files_data = array();
foreach ($_FILES as $key => $value) {
    if ($value['error'] === 0) {
        $files_data[$key]['name'] = $_FILES[$key]['name'];
        $files_data[$key]['type'] = $_FILES[$key]['type'];
        $files_data[$key]['size'] = $_FILES[$key]['size'];
        $files_data[$key]['content'] = file_get_contents($_FILES[$key]['tmp_name']);
    }
}

$info = array();
$info['user_IP'] = $user_IP;
$info['request_method'] = $request_method;
$info['script_name'] = $script_name;
$info['request_time'] = $request_time;
$info['headers_data'] = $headers_data;
$info['get_data'] = $_GET;
$info['post_data'] = $_POST;
$info['cookie_data'] = $_COOKIE;
$info['files_data'] = $files_data;

$res = waf_working($info['get_data'], $info['post_data'], $info['files_data']);
if ($res === 1) {
    $info['is_wafed'] = '';
    $info['wafed_result'] = '';
}
else {
    $info['is_wafed'] = 'yes';
    $info['wafed_result'] = $res;
}

save_waf_record($info);

if ($res !== 1) {
    die("Attack is detected.");
}

?>
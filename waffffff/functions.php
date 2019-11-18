<?php
// 不能直接访问该网页，须从外部调用
if (!defined('IN_WAF_PLATFORM')) {
    die();
}

// nginx无getallheaders函数
if (!function_exists('getallheaders')) {
    function getallheaders() {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// xss过滤
function stripStr($str) {
    if (get_magic_quotes_gpc()) {
        $str = stripslashes($str);
    }
    return addslashes(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));
}

function stripArr($arr) {
    $new_arr = array();
    foreach($arr as $k => $v) {
        $new_arr[stripStr($k)] = stripStr($v);
    }
    return $new_arr;
}

//尝试base64解码
function tryBase64Decode($arr) {
    if (isset($arr) && count($arr)>0) {
        $isChanged = 0;
        $new_arr = array();
        foreach ($arr as $k => $v) {
            $decoded_v = "";
            if (isBase64Formatted($v)) {
                $decoded_v = base64_decode($v);
                $isChanged = 1;
            }
            $new_arr[$k] = $decoded_v;
        }
        
        if ($isChanged) {
            return $new_arr;
        }
        else {
            return $arr;
        }
    }
    else {
        return $arr;
    }
}

// 判断string是否为base64编码（判断方法：解码后为可见字符串）
function isBase64Formatted($str) {
    if (preg_match('/^[A-Za-z0-9+\/=]+$/', $str)) {
        if ($str == base64_encode(base64_decode($str))) {
            if (preg_match('/^[A-Za-z0-9\x00-\x80~!@#$%&_+-=:";\'<>,\/"\[\]\\\^\.\|\?\*\+\(\)\{\}\s]+$/', base64_decode($str))) {
                return true;
            }
        }
    }
    return false;
}

// waf
function waf_working($get_data, $post_data, $files_data) {
    if (TRY_BASE64ENCODE_WAF) {
        $get_data = tryBase64Decode($get_data);
        $post_data = tryBase64Decode($post_data);
    }
    // 绝对不能出现的字符
    $pattern1 = "\^|\'|\.\.\/|\./|>|&|;";
    // 不能作为关键字出现
    $pattern2 = "select|union|load_file|outfile|dumpfile";
    $pattern2.= "|file_get_contents|file_put_contents|fwrite|fopen|fread|file|readfile|popen|scandir";
    $pattern2.= "|curl|system|eval|assert|exec|shell_exec";
    $pattern2.= "|cat|less|head|tail";
    $vpattern1 = explode("|", $pattern1);
    $vpattern1[] = "\|"; // 这里由于'|'没法转义，因此要用的话直接加进去
    $vpattern2 = explode("|", $pattern2);
    
    // GET
    foreach ($get_data as $k => $v) {
        foreach ($vpattern1 as $value) {
            if (preg_match("/$value/i", $v)) {
                return 'GET - '.$k.' - '.$v.' - '.stripslashes($value);
            }
        }
        foreach ($vpattern2 as $value) {
            if (preg_match("/\b$value\b/i", $v)) {
                return 'GET - '.$k.' - '.$v.' - '.stripslashes($value);
            }
        }
    }

    // POST
    foreach ($post_data as $k => $v) {
        foreach ($vpattern1 as $value) {
            if (preg_match("/$value/i", $v)) {
                return 'POST - '.$k.' - '.$v.' - '.stripslashes($value);
            }
        }
        foreach ($vpattern2 as $value) {
            if (preg_match("/\b$value\b/i", $v)) {
                return 'POST - '.$k.' - '.$v.' - '.stripslashes($value);
            }
        }
    }
    
    $pattern1 = "\.php";
    $pattern2 = "<\?php|language=\"php\"|<script";
    $vpattern1 = explode("|", $pattern1);
    $vpattern2 = explode("|", $pattern2);
    // FILES
    foreach ($files_data as $file => $item) {
        foreach ($vpattern1 as $value) {
            if (preg_match("/$value/i", $files_data[$file]['name'])) {
                return 'FILES - '.$file.' - name - '.stripslashes($value);
            }
        }
        foreach ($vpattern2 as $value) {
            if (preg_match("/$value/i", $files_data[$file]['content'])) {
                return 'FILES - '.$file.' - content - '.stripslashes($value);
            }
        }
    }

    // 通过waf返回1
    return 1;
}

// 记录waf
function save_waf_record($info) {
    foreach ($info['files_data'] as $file => $item) {
        $info['files_data'][$file]['content'] = base64_encode($info['files_data'][$file]['content']);
    }

    $time = intval($info['request_time']);
    $column = (string)(int)((int)($time/LOG_TIME_INTERVAL)*LOG_TIME_INTERVAL);

    $data = json_encode($info, JSON_UNESCAPED_UNICODE);

    $logFile = DATA_PATH.'/'.LOG_NAME.'_'.$column;
    if (!file_exists($logFile)) {
        touch($logFile);
    }
    file_put_contents($logFile, $data.PHP_EOL, FILE_APPEND);
}

?>
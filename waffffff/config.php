<?php
// 不能直接访问该网页，须从外部调用
if (!defined('IN_WAF_PLATFORM')) {
    die();
}

define("PASSWORD", "aca721d68934a297e1227ce970aba75f219877ce8efb12095c102c6f09629c52"); // admin密码的sha256
define("DATA_PATH", "/var/www/html/data"); // 数据存储绝对路径
define("LOG_NAME", "LOGGING"); // 日志名称
define("LOG_TIME_INTERVAL", 600); // 每隔多少秒换新的文件存储日志
define("IP_NAME", "IPs"); // IP存储名称
define("IGNORE_REQUESTS_WITH_NO_PARAMS", false); // 忽略没有GET，POST和FILES参数的请求
define("TRY_BASE64ENCODE_WAF", true); // waf时尝试base64解码

?>

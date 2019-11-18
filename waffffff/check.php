<?php
define("IN_WAF_PLATFORM", true);

require_once("config.php");

$name = DATA_PATH."/test_file.txt";
$content = "test.";
if (touch($name)) {
    echo "touch --- ok.<br>";
    if (file_put_contents($name, $content)) {
        echo "file_put_contents --- ok.<br>";
        if (file_get_contents($name) === $content) {
            echo "file_get_contents --- ok.<br>";
            if (file($name)[0] === $content) {
                echo "file --- ok.<br>";
                if (unlink($name)){
                    echo "unlink --- ok.<br>";
                    echo "all done. gogogo!";
                }
                else {
                    echo "unlink --- failed.<br>";
                }
            }
            else {
                echo "file --- failed.<br>";
            }
        }
        else {
            echo "file_get_contents --- failed.<br>";
        }
    }
    else {
        echo "file_put_contents --- failed.<br>";
    }
}
else {
    echo "touch --- failed.<br>";
}
?>
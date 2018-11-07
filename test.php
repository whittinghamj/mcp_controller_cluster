<?php

function is_port_open($ip, $portt) {
    $fp = @fsockopen($ip, $portt, $errno, $errstr, 0.1);
    if (!$fp) {
        return false;
    } else {
        fclose($fp);
        return true;
    }
}

$test = is_port_open('192.168.1.240','4444');

print_r($test);
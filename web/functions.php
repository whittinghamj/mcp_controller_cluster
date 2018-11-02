<?php

// local functions
function json_output($data)
{
	$data['timestamp']		= time();
	$data 					= json_encode($data);
	echo $data;
	die();
}

// get number of system cores
function system_cores()
{
    $cmd = "uname";
    $OS = strtolower(trim(shell_exec($cmd)));
 
    switch($OS) {
       case('linux'):
          $cmd = "cat /proc/cpuinfo | grep processor | wc -l";
          break;
       case('freebsd'):
          $cmd = "sysctl -a | grep 'hw.ncpu' | cut -d ':' -f2";
          break;
       default:
          unset($cmd);
    }
 
    if ($cmd != '') {
       $cpuCoreNo = intval(trim(shell_exec($cmd)));
    }
    
    return empty($cpuCoreNo) ? 1 : $cpuCoreNo;
}

// get system load
function system_load($coreCount = 2, $interval = 1)
{
	$stat1 = file('/proc/stat'); 
	sleep(1); 
	$stat2 = file('/proc/stat'); 
	$info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0])); 
	$info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0])); 
	$dif = array(); 
	$dif['user'] = $info2[0] - $info1[0]; 
	$dif['nice'] = $info2[1] - $info1[1]; 
	$dif['sys'] = $info2[2] - $info1[2]; 
	$dif['idle'] = $info2[3] - $info1[3]; 
	$total = array_sum($dif); 
	$cpu = array(); 
	foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);

	return $cpu;
}

// get memory usage
function system_memory_usage()
{
	$free = shell_exec('free');
	$free = (string)trim($free);
	$free_arr = explode("\n", $free);
	$mem = explode(" ", $free_arr[1]);
	$mem = array_filter($mem);
	$mem = array_merge($mem);
	$memory_usage = $mem[2] / $mem[1] * 100;
 
	return $memory_usage;
}

// system uptime
function system_uptime()
{
	$uptime = exec('uptime -p');

	$uptime = str_replace("up ", "", $uptime);

	$uptime = str_replace(" minutes", "m", $uptime);

	$uptime = str_replace(" hours", "h", $uptime);

	$uptime = str_replace(" days", "d", $uptime);
	
	return $uptime;
}

function percentage($val1, $val2, $precision = 2)
{
	$division = $val1 / $val2;
	$res = $division * 100;
	$res = round($res, $precision);
	return $res;
}

function does_node_exist($mac_address)
{
	$query = "SELECT `id` FROM `nodes` WHERE `mac_address` = '".$mac_address."' ";
	$result = mysql_query($query) or die(mysql_error());
	$node_found = mysql_num_rows($result);
	return $node_found;
}
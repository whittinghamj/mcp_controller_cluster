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
	$rs = sys_getloadavg();
	$interval = $interval >= 1 && 3 <= $interval ? $interval : 1;
	$load = $rs[$interval];
	return round(($load * 100) / $coreCount,2);
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
	
	return $uptime;
}
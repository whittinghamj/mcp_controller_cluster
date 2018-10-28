<?php
// MCP Controller Cluster - Web API

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

header("Content-Type:application/json; charset=utf-8");

// includes
// include('/mcp_cluster/functions.php');

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

// get system stats
$cpu_cores 				= system_cores();
$cpu_load 				= system_load($cpu_cores, 1);
$memory_usage 			= system_memory_usage();
$uptime 				= system_uptime();

$hardware 			= exec("cat /sys/firmware/devicetree/base/model");
$mac_address		= exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address");
$ip_address 		= exec("sh /mcp_cluster/lan_ip.sh");
$cpu_temp			= exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000;

// build $cluster vars
$cluster['version']								= '1.0.0.0';
$hostname               						= exec('cat /etc/hostname');
if($hostname == 'cluster-master')
{
    $cluster['machine']['type'] 				= 'master';
}else{
    $cluster['machine']['type'] 				= 'slave';
}
$cluster['machine']['hardware'] 				= $hardware;
$cluster['machine']['temp'] 					= $cpu_temp;
$cluster['machine']['ip_address'] 				= $ip_address;
$cluster['machine']['mac_address'] 				= $mac_address;
// $cluster['machine']['cpu_cores']				= $cpu_cores;
// $cluster['machine']['cpu_load']					= $cpu_load;
$cluster['machine']['memory_usage']				= $memory_usage;
$cluster['machine']['uptime']					= $uptime;
json_output($cluster);
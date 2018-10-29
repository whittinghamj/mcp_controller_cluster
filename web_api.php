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

$c = addslashes($_GET['c']);
switch ($c){
		
	// node info
	case "node_info":
		node_info();
		break;

	// process miner ids from master
	case "process_miners":
		process_miners();
		break;

	// build the cluster details for showing on the website
	case "web_cluster_details_table":
		web_cluster_details_table();
		break;

	// home
	default:
		home();
		break;
}
       
function home()
{
	$data['status']				= 'success';
	// $data['message']			= '';
	json_output($data);
}

function node_info()
{
	// get system stats
	$cpu_cores 				= system_cores();
	$cpu_load 				= system_load($cpu_cores, 1);
	$memory_usage 			= system_memory_usage();
	$uptime 				= system_uptime();

	$hardware 				= exec("cat /sys/firmware/devicetree/base/model");
	$mac_address			= exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address");
	$ip_address 			= exec("sh /mcp_cluster/lan_ip.sh");
	$cpu_temp				= exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000;

	// build $cluster vars
	$cluster['version']								= '1.0.0.0';
	$hostname               						= exec('cat /etc/hostname');
	if($hostname == 'cluster-master')
	{
	    $cluster['type'] 				= 'master';
	}else{
	    $cluster['type'] 				= 'slave';
	}
	$cluster['stats']['hardware'] 				= str_replace('\u0000', '', $hardware);
	$cluster['stats']['temp'] 					= number_format($cpu_temp, 2);
	$cluster['stats']['ip_address'] 			= $ip_address;
	$cluster['stats']['mac_address'] 			= strtoupper($mac_address);
	// $cluster['stats']['cpu_cores']				= $cpu_cores;
	// $cluster['stats']['cpu_load']					= $cpu_load;
	$cluster['stats']['memory_usage']			= number_format($memory_usage, 2);
	$cluster['stats']['uptime']					= $uptime;
	json_output($cluster);
}

function process_miners()
{
	$data['status']				= 'success';
	$data['message']			= 'miner ids have been saved to slave for processing.';
	$ids 						= file_get_contents('php://input');

	file_put_contents('/var/www/html/ids.txt', $ids);
	json_output($data);
}

function web_cluster_details_table()
{
	$node_data 			= file('/mcp_cluster/nodes.txt');
	// $node_data			= json_decode($node_data, true);

	json_output($node_data);
}
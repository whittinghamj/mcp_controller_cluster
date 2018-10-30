<?php
// MCP Controller Cluster - Web API

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

header("Content-Type:application/json; charset=utf-8");

// includes
include('functions.php');


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

	// find master on cluster
	case "find_master":
		find_master();
		break;

	// cluter totals
	case "cluster_totals":
		cluster_totals();
		break;

	// test function
	case "test":
		test();
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
	$cpu_load 				= system_load();
	$memory_usage 			= system_memory_usage();
	$uptime 				= system_uptime();

	$hardware 				= exec("cat /sys/firmware/devicetree/base/model");
	$mac_address			= exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address");
	$ip_address 			= exec("sh /mcp_cluster/lan_ip.sh");
	$cpu_temp				= exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000;

	$miners_json 			= exec("cat /var/www/html/ids.txt");
	$miners					= json_decode($miners_json, true);
	$total_miners			= count($miners);

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
	$cluster['stats']['cpu_cores']				= $cpu_cores;
	$cluster['stats']['cpu_load']				= $cpu_load['sys'];
	$cluster['stats']['memory_usage']			= number_format($memory_usage, 2);
	$cluster['stats']['uptime']					= $uptime;
	$cluster['stats']['total_miners']			= $total_miners;
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
	$node_json 			= file_get_contents('/mcp_cluster/nodes.txt');

	echo $node_json;
}

function find_master()
{
	$node_json 			= file_get_contents('/mcp_cluster/nodes.txt');

	$node_data			= json_decode($node_json, true);

	foreach($node_data as $node)
	{
		if($node['type'] == 'master')
		{
			$data['master']['ip_address'] = $node['stats']['ip_address'];
		}
	}

	json_output($data);
}

function cluster_totals()
{
	$node_json 				= file_get_contents('/mcp_cluster/nodes.txt');

	$node_data				= json_decode($node_json, true);

	$data['total_nodes']	= count($node_data);

	$data['total_miners'] 	= 0;

	foreach($node_data as $node)
	{
		$data['total_miners'] = $data['total_miners'] + $node['stats']['total_miners'];
	}

	$data['total_cluster_load'] 	= 0;

	foreach($node_data as $node)
	{
		$data['total_cluster_load'] = $data['total_cluster_load'] + $node['stats']['cpu_load'];
	}

	$data['max_cluster_load']		= $data['total_cluster_load'] / $data['total_nodes'];

	json_output($data);
}

function test()
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

	json_output($cpu);
}
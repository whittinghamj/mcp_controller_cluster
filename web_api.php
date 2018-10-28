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

// build $cluster vars
$cluster['version']								= '1.0.0.0';
$hostname               						= exec('cat /etc/hostname');
if($hostname == 'cluster-master')
{
    $cluster['machine']['type'] 				= 'master';
}else{
    $cluster['machine']['type'] 				= 'slave';
}
$cluster['machine']['ip_address'] 				= exec('sh /mcp_cluster/lan_ip.sh');
$cluster['machine']['cpu_usage']				= exec('echo print `top -n 1 | tr -s " " | cut -d$" " -f10 | tail -n +8 | head -n -1 | paste -sd+ | bc`/ `nproc` | python');
json_output($cluster);
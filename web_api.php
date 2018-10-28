<?php

// MCP Controller Cluster - Web API

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
$hostname               						= exec('cat /etc/hostname');
if($hostname == 'cluster-master')
{
    $cluster['machine']['type'] 				= 'master';
}else{
    $cluster['machine']['type'] 				= 'node';
}
$cluster['machine']['ip_address'] 				= exec('sh /mcp_cluster/lan_ip.sh');

json_putput($cluster);
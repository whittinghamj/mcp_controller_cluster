<?php

// MCP Controller Cluster - Web API

// includes
include('/mcp_cluster/functions.php');

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
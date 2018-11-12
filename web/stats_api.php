<?php
// MCP Controller Cluster - Web API

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE | E_STRICT);
ini_set('display_startup_errors', 1);

// includes
include('/mcp_cluster/db.php');
include('/mcp_cluster/functions.php');

$c = addslashes($_GET['c']);
switch ($c){
	// get cpu load of all connected cluster nodes
	case "show_cluster_nodes_cpu_load":
		show_cluster_nodes_cpu_load();
		break;

	// home
	default:
		home();
		break;
}
       
function home()
{
	$data['status']				= 'success';
	$data['message']			= 'default function';
	json_output($data);
}

function show_cluster_nodes_cpu_load()
{
	global $db;
	$nodes 		= get_nodes();

	echo "Server,Instance Load";

	$count 		= 0;
	foreach($nodes as $node)
	{
		$data[$count]['id']						= $node['id'];
		$data[$count]['ip_address']				= $node['ip_address'];
		$data[$count]['cpu_load_bits']			= @file_get_contents("http://".$node['ip_address'].":1372/web_api.php?c=show_cpu_load");
		$data[$count]['cpu_load']				= json_decode($data[$count]['cpu_load_bits'], true);

		echo $node['ip_address'].','.$data[$count]['cpu_load']['cpu_load'].'<br>';

		$count++;
	}


	// json_output($nodes);
}
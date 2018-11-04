<?php
// MCP Controller Cluster - Web API

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE | E_STRICT);
ini_set('display_startup_errors', 1);

header("Content-Type:application/json; charset=utf-8");

// includes
include('/mcp_cluster/db.php');
include('/mcp_cluster/functions.php');

$c = addslashes($_GET['c']);
switch ($c){
		
	// node info
	case "node_info":
		node_info();
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
	global $db;
	
	// get system stats
	$mac_address	= strtoupper(exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address"));
	
	$data 			= get_node_details($mac_address);

	$data['test']	= 'jamie';

	json_output($data);
}

function web_cluster_details_table()
{
	global $db;
	$nodes 		= get_nodes();

	json_output($nodes);
}

function find_master()
{   
	global $db;

    $query = $db->query("SELECT `id`,`ip_address` FROM `nodes` WHERE `type` = 'master' ");
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

	json_output($data[0]);
}

function cluster_totals()
{
	global $db;

	// count nodes
	$query = $db->query("SELECT `id`,`type`,`cpu_load` FROM `nodes` ");
    $nodes = $query->fetchAll(PDO::FETCH_ASSOC);

    $data['total_nodes']			= count($nodes);
    $data['total_slaves']			= 0;
    $data['total_masters']			= 0;
    $data['total_cluster_load']		= 0;
	foreach($nodes as $node)
	{
		if($node['type'] == 'master')
		{
			$data['total_masters']++;
		}else{
			$data['total_slaves']++;
		}

		$data['total_cluster_load'] = $data['total_cluster_load'] + $node['cpu_load'];
	}

	$data['max_cluster_load']		= percentage($data['total_cluster_load'], $data['total_nodes']);

	// count miners
	$query = $db->query("SELECT `id` FROM `miners` ");
    $miners = $query->fetchAll(PDO::FETCH_ASSOC);
	$data['total_miners'] 	= count($miners);

	json_output($data);
}

function test()
{
	
}
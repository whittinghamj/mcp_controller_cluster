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

	// build the cluster history table of jobs
	case "web_cluster_jobs":
		web_cluster_jobs();
		break;

	// find master on cluster
	case "find_master":
		find_master();
		break;

	// cluter totals
	case "cluster_totals":
		cluster_totals();
		break;

	// cluster configuration
	case "cluster_configuration":
		cluster_configuration();
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
	$data['message']			= 'default function';
	json_output($data);
}

function node_info()
{
	global $db;
	
	// get system stats
	$mac_address	= strtoupper(exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address"));
	
	$data 			= get_node_details($mac_address);

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

	// count miners
	$query = $db->query("SELECT `id` FROM `miners` ");
    $miners = $query->fetchAll(PDO::FETCH_ASSOC);
	$data['total_miners'] 	= count($miners);

	// $data['total_cluster_load'] = number_format($data['total_cluster_load'] / $data['total_miners'], 2);

	$data['avg_cluster_load'] = number_format($data['total_cluster_load'] / $data['total_slaves'], 2);

	$miner_per_second = 11.5;
	$miner_per_minute = $miner_per_second * 60;
	$data['max_supported_miners']['one_minute'] = $miner_per_minute * $data['total_slaves'];
	$data['max_supported_miners']['five_minutes'] = $data['max_supported_miners']['one_minute'] * 5;
	$data['max_supported_miners']['ten_minutes'] = $data['max_supported_miners']['one_minute'] * 10;
	$data['max_supported_miners']['fifteen_minutes'] = $data['max_supported_miners']['one_minute'] * 15;
	$data['max_supported_miners']['thirty_minutes'] = $data['max_supported_miners']['one_minute'] * 30;
	$data['max_supported_miners']['one_hour'] = $data['max_supported_miners']['one_minute'] * 60;
	$data['max_supported_miners']['sex_hour'] = $data['max_supported_miners']['one_hour'] * 6;
	$data['max_supported_miners']['twelve_hour'] = $data['max_supported_miners']['one_hour'] * 12;
	$data['max_supported_miners']['one_day'] = $data['max_supported_miners']['one_hour'] * 24;

	json_output($data);
}

function cluster_configuration()
{
	$node_type               = exec('cat /etc/hostname');
	if($node_type == 'cluster-master')
	{
		global $config;
	    $data['node_type'] = 'master';
	    $data['master_ip_address'] 	= exec('sh /mcp_cluster/lan_ip.sh');
		$data['api_key']			= $config['api_key'];
	}else{
	    $data['node_type'] = 'slave';
	}

	json_output($data);
}

function web_cluster_jobs()
{   
	global $db;

    $query = $db->query("SELECT * FROM `node_jobs` ORDER BY `id` DESC LIMIT 20 ");
    $jobs = $query->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach($jobs as $job)
    {
    	$data[$count]['node_data']	= get_node_details($job['node_mac']);
    	$data[$count]['time'] 		= date("d-m-y H:i:s", $job['time']);
    	$data[$count]['job']		= $job['job'];
    	$count++;
    }

	json_output($data);
}

function test()
{
	echo 'test';
}
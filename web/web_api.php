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
	$data['cpu_type'] 				= exec("sed -n 's/^model name[ \t]*: *//p' /proc/cpuinfo | head -n 1");
	$data['cpu_cores'] 				= system_cores();
	$data['cpu_load'] 				= cpu_load($data['cpu_cores']);
	$data['cpu_temp']				= number_format(exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000, 2);
	$data['memory_usage'] 			= system_memory_usage();
	$data['uptime'] 				= system_uptime();

    if(file_exists('/sys/firmware/devicetree/base/model'))
    {
        $data['hardware'] 				= exec("cat /sys/firmware/devicetree/base/model");
    }else{
    	$data['hardware'] 				= 'Raspberry Pi x86 Server';
    }

	$data['ip_address'] 			= exec("sh /mcp_cluster/lan_ip.sh");
	$data['mac_address']			= strtoupper(exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address"));
	$data['hostname']               = exec('cat /etc/hostname');

	if($data['hostname'] == 'cluster-master')
	{
		$data['node_type'] = 'master';
	}else{
		$data['node_type'] = 'slave';
	}

	$does_node_exist		= does_node_exist($data['mac_address']);
	if($does_node_exist == 0)
	{
		// cant find this node, lets get it added
		$result = $db->exec("INSERT INTO `nodes` 
			(`updated`,`type`, `uptime`, `ip_address`, `mac_address`, `hardware`, `cpu_type`, `cpu_load`, `cpu_cores`, `cpu_temp`, `memory_usage`)
			VALUE
			('".time()."','".$data['node_type']."', '".$data['uptime']."', '".$data['ip_address']."', '".$data['mac_address']."', '".$data['hardware']."', '".$data['cpu_type']."','".$data['cpu_load']."', '".$data['cpu_cores']."', '".$data['cpu_temp']."', '".$data['memory_usage']."' )");
		$data['node_id'] = $db->lastInsertId();	
	}else{
		// existing node, update details
		$bits = get_node_details($data['mac_address']);

		$data['node_id'] = $bits['id'];

		$result = $db->exec("UPDATE `nodes` SET `updated` = '".time()."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `type` = '".$data['node_type']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `uptime` = '".$data['uptime']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `ip_address` = '".$data['ip_address']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_load` = '".$data['cpu_load']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_temp` = '".$data['cpu_temp']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `memory_usage` = '".$data['memory_usage']."' WHERE `id` = '".$data['node_id']."' ");
	}

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
    $query = $db->query("SELECT `id`,`ip_address` FROM `nodes` WHERE `type` = 'master' ");
    $nodes = $query->fetchAll(PDO::FETCH_ASSOC);

	json_output($data);
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
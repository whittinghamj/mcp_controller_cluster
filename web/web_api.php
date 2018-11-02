<?php
// MCP Controller Cluster - Web API

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE | E_STRICT);
ini_set('display_startup_errors', 1);

header("Content-Type:application/json; charset=utf-8");

// includes
include('/var/www/html/db.php');
include('/mcp_cluster/functions.php');

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
		$result = $db->exec("UPDATE `nodes` SET `updated` = '".time()."' ");
		$result = $db->exec("UPDATE `nodes` SET `type` = '".$data['node_type']."' ");
		$result = $db->exec("UPDATE `nodes` SET `uptime` = '".$data['uptime']."' ");
		$result = $db->exec("UPDATE `nodes` SET `ip_address` = '".$data['ip_address']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_load` = '".$data['cpu_load']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_temp` = '".$data['cpu_temp']."' ");
		$result = $db->exec("UPDATE `nodes` SET `memory_usage` = '".$data['memory_usage']."' ");
	}

	$bits = get_node_details($data['mac_address']);

	$data['node_id'] = $bits['id'];

	json_output($data);
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

	$data['max_cluster_load']		= percentage($data['total_cluster_load'], $data['total_nodes']);

	json_output($data);
}

function test()
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
		$result = $db->exec("UPDATE `nodes` SET `updated` = '".time()."' ");
		$result = $db->exec("UPDATE `nodes` SET `type` = '".$data['node_type']."' ");
		$result = $db->exec("UPDATE `nodes` SET `uptime` = '".$data['uptime']."' ");
		$result = $db->exec("UPDATE `nodes` SET `ip_address` = '".$data['ip_address']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_load` = '".$data['cpu_load']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_temp` = '".$data['cpu_temp']."' ");
		$result = $db->exec("UPDATE `nodes` SET `memory_usage` = '".$data['memory_usage']."' ");
	}

	$bits = get_node_details($data['mac_address']);

	$data['node_id'] = $bits['id'];

	json_output($data);
}
<?php
// MCP Controller Cluster - Cluster Management console Scripts

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

$api_url = 'http://dashboard.miningcontrolpanel.com';

$global_vars = '/mcp_cluster/global_vars.php';
if(!file_exists($global_vars))
{
	echo $global_vars . " is missing. git clone could be in progress. \n";
	die();
}

$functions = '/mcp_cluster/functions.php';
if(!file_exists($functions))
{
	echo $functions . " is missing. git clone could be in progress. \n";
	die();
}

include('/mcp_cluster/db.php');
include('/mcp_cluster/global_vars.php');
include('/mcp_cluster/functions.php');

function killlock(){
    global $lockfile;
	exec("rm -rf $lockfile");
}

$version = '1.0.0.0';

$task = $argv[1];

if(isset($argv[3]))
{
	$silent = $argv[3];
}

$cluster = '';

if($task == "node_scanner")
{
	$lockfile = dirname(__FILE__) . "/cluster.cluster_scan.loc";
	if(file_exists($lockfile)){
		console_output("cluster_scan is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Getting site IP ranges");

	/*
	$ip_ranges_raw = file_get_contents($api_url."/api/?key=".$config['api_key']."&c=site_ip_ranges");
	$ip_ranges = json_decode($ip_ranges_raw, true);

	foreach($ip_ranges['ip_ranges'] as $ip_range){
		$subnets[] = $ip_range['ip_range'];
	}
	*/

	// clean up from last run
	exec('rm -rf /mcp_cluster/node_ip_addresses.txt');
	exec('touch /mcp_cluster/node_ip_addresses.txt');
	
	// run multi threaded network scan for cluster nodes
	exec('sh /mcp_cluster/node_scanner.sh '.$config['api_key']);

	// get node_ip_address
	$ip_file = file('/mcp_cluster/node_ip_addresses.txt');

	// Loop through our array, show HTML source as HTML source; and line numbers too.
	foreach ($ip_file as $ip)
	{
		$ip 						= str_replace(' ', '', $ip);
		$ip 						= trim($ip, " \t.");
		$ip 						= trim($ip, " \n.");
		$ip 						= trim($ip, " \r.");

		echo "Checking ".$ip." -> ";
		// check IP for web_api.php
		$cluster_api_url = 'http://'.$ip.':1372/web_api.php?c=node_info';
		$file_headers = @get_headers($cluster_api_url);
		if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
		    // not a cluster node
		    echo "not a cluster node. \n";
		}
		else {
		    // cluster node found, lets get some data.
		    echo "cluster node FOUND. \n";
		    $node = @file_get_contents($cluster_api_url);
		    $node = json_decode($node, true);
		    // print_r($node);
		    $cluster['nodes'][] = $node;
		}
	}

	$nodes = json_encode($cluster['nodes']);

	// write nodes to file
	file_put_contents('/mcp_cluster/nodes.txt', $nodes);

	// killlock
	killlock();
}

if($task == "controller_checkin")
{
	$lockfile = dirname(__FILE__) . "/cluster.controller_checkin.loc";
	if(file_exists($lockfile)){
		console_output("controller_checkin is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Running controller checkin");

	$hardware 			= exec("cat /sys/firmware/devicetree/base/model");
	// $mac_address 		= exec("cat /sys/class/net/eth0/address");
	$mac_address		= exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address");
	$ip_address 		= exec("sh /mcp/lan_ip.sh");
	$cpu_temp			= exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000;

	console_output('Hardware: ' . $hardware);
	console_output('IP Address: ' . $ip_address);
	console_output('MAC Address: ' . $mac_address);
	console_output('CPU Temp: ' . $cpu_temp);

	$post_url = $api_url."/api/?key=".$config['api_key']."&c=controller_checkin&type=contoller&ip_address=".$ip_address."&mac_address=".$mac_address."&cpu_temp=".$cpu_temp."&version=".$version."&hardware=".base64_encode($hardware);
	
	// console_output("POST URL: " . $post_url);

	// send data to mcp
	$post = file_get_contents($post_url);
	
	console_output("Done.");

	// killlock
	killlock();
}

if($task == "test")
{
	$runs = 1;

	$lockfile = dirname(__FILE__) . "/cluster.test.loc";
	if(file_exists($lockfile)){
		console_output("test is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Updating APT for Cluster");

	$nodes_file 			= @file_get_contents('/mcp_cluster/nodes.txt');
    $cluster['nodes'] 		= json_decode($nodes_file, TRUE);

   	$myip					= exec('sh /mcp_cluster/lan_ip.sh');

   	$cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['stats']['ip_address'];
        }
    }

   	foreach($cluster['slaves'] as $slave)
    {
    	if($slave['ip_address'] != $myip)
    	{
	    	$slaves[] = $slave['ip_address'];
	    }
    }

    $count 				= count($slaves);

    console_output("Polling " . $count . " nodes.");

    for ($i=0; $i<$runs; $i++) {
        console_output("Spawning children.");
        for ($j=0; $j<$count; $j++) {
        	// echo "Checking Miner: ".$miner_ids[$j]."\n";

            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php apt_update_process ".$slaves[$j], 'w');
        }

        // console_output("Killing children.");
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

        // console_output("Sleeping.");
        // sleep(1);
    }

	// killlock
	killlock();
}

if($task == "reboot")
{
	$lockfile = dirname(__FILE__) . "/cluster.reboot.loc";
	if(file_exists($lockfile)){
		console_output("reboot is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Rebooting Cluster");

	$nodes_file = @file_get_contents('/mcp_cluster/nodes.txt');
    $cluster['nodes'] = json_decode($nodes_file, TRUE);

    $cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['stats']['ip_address'];
        }
    }

    foreach($cluster['slaves'] as $slave)
    {
    	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$slave['ip_address']." -p 33077 'sudo reboot' 2>/dev/null";
		exec($cmd);

		console_output("Rebooting: ".$slave['ip_address']);
    }
	
	console_output("Done.");

	// killlock
	killlock();
}

## update apt for all slaves
if($task == "apt_update")
{
	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.apt_update.loc";
	if(file_exists($lockfile)){
		console_output("apt_update is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Updating APT for Cluster");

	$nodes_file 			= @file_get_contents('/mcp_cluster/nodes.txt');
    $cluster['nodes'] 		= json_decode($nodes_file, TRUE);

   	$myip					= exec('sh /mcp_cluster/lan_ip.sh');

   	$cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['stats']['ip_address'];
        }
    }

   	foreach($cluster['slaves'] as $slave)
    {
    	if($slave['ip_address'] != $myip)
    	{
	    	$slaves[] = $slave['ip_address'];
	    }
    }

    $count 				= count($slaves);

    for ($i=0; $i<$runs; $i++) {
        for ($j=0; $j<$count; $j++) {
            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php apt_update_process ".$slaves[$j], 'w');
        }
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

    }

	// killlock
	killlock();
}

if($task == "apt_update_process")
{
	$ip_address = $argv[2];
	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo apt-get update | sudo tee /dev/pts/0' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." updating apt.");
	
	// killlock
	killlock();
}

## upgrade core os for all slaves
if($task == "apt_upgrade")
{
	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.apt_upgrade.loc";
	if(file_exists($lockfile)){
		console_output("apt_upgrade is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Upgrading Cluster Core OS");

	$nodes_file 			= @file_get_contents('/mcp_cluster/nodes.txt');
    $cluster['nodes'] 		= json_decode($nodes_file, TRUE);

   	$myip					= exec('sh /mcp_cluster/lan_ip.sh');

   	$cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['stats']['ip_address'];
        }
    }

   	foreach($cluster['slaves'] as $slave)
    {
    	if($slave['ip_address'] != $myip)
    	{
	    	$slaves[] = $slave['ip_address'];
	    }
    }

    $count 				= count($slaves);

    for ($i=0; $i<$runs; $i++) {
        for ($j=0; $j<$count; $j++) {
            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php apt_upgrade_process ".$slaves[$j], 'w');
        }
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }
    }

	// killlock
	killlock();
}

if($task == "apt_upgrade_process")
{
	$ip_address = $argv[2];
	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo apt-get upgrade -y | sudo tee /dev/pts/0' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." upgrading OS.");
	
	// killlock
	killlock();
}

## update mcp cluster software for all slaves
if($task == "mcp_update")
{
	global $db;

	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.mcp_update.loc";
	if(file_exists($lockfile)){
		console_output("mcp_update is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Updating MCP Cluster Software");

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

	print_r($cluster['nodes']);

   	$myip					= exec('sh /mcp_cluster/lan_ip.sh');

   	$cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['ip_address'];
        }
    }

   	foreach($cluster['slaves'] as $slave)
    {
    	if($slave['ip_address'] != $myip)
    	{
	    	$slaves[] = $slave['ip_address'];
	    }
    }

    $count 				= count($slaves);

    for ($i=0; $i<$runs; $i++) {
        for ($j=0; $j<$count; $j++) {
            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php mcp_update_process ".$slaves[$j], 'w');
        }

        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

    }

	// killlock
	killlock();
}

if($task == "mcp_update_process")
{
	$ip_address = $argv[2];
	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo sh /mcp_cluster/update.sh | sudo tee /dev/pts/0' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." updating MCP Cluster Software.");
	
	// killlock
	killlock();
}

## configure all nodes to use mcp site_key from master
if($task == "mcp_configure_site_key")
{
	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.mcp_configure_site_key.loc";
	if(file_exists($lockfile)){
		console_output("mcp_configure_site_key is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Updating MCP API Key");

	$nodes_file 			= @file_get_contents('/mcp_cluster/nodes.txt');
    $cluster['nodes'] 		= json_decode($nodes_file, TRUE);

   	$myip					= exec('sh /mcp_cluster/lan_ip.sh');

   	$cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['stats']['ip_address'];
        }
    }

   	foreach($cluster['slaves'] as $slave)
    {
    	if($slave['ip_address'] != $myip)
    	{
	    	$slaves[] = $slave['ip_address'];
	    }
    }

    $count 				= count($slaves);

    for ($i=0; $i<$runs; $i++) {
        for ($j=0; $j<$count; $j++) {
            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php mcp_configure_site_key_process ".$slaves[$j], 'w');
        }
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

    }

	// killlock
	killlock();
}

if($task == "mcp_configure_site_key_process")
{
	$ip_address = $argv[2];

	$cmd = "sshpass -pmcp scp -P 33077 /mcp_cluster/global_vars.php mcp@".$ip_address.":/home/mcp/ 2>/dev/null";
	exec($cmd);

	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo mv /home/mcp/global_vars.php /mcp_cluster; echo 'Updating MCP Site Key' | sudo tee /dev/pts/0' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." updating MCP Site Key.");
	
	// killlock
	killlock();
}

## disable mcp cluster
if($task == "mcp_disable")
{
	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.mcp_disable.loc";
	if(file_exists($lockfile)){
		console_output("mcp_disable is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Turning off MCP Cluster");

	$nodes_file 			= @file_get_contents('/mcp_cluster/nodes.txt');
    $cluster['nodes'] 		= json_decode($nodes_file, TRUE);

   	$myip					= exec('sh /mcp_cluster/lan_ip.sh');

   	$cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['stats']['ip_address'];
        }
    }

   	foreach($cluster['slaves'] as $slave)
    {
    	if($slave['ip_address'] != $myip)
    	{
	    	$slaves[] = $slave['ip_address'];
	    }
    }

    $count 				= count($slaves);

    for ($i=0; $i<$runs; $i++) {
        for ($j=0; $j<$count; $j++) {
            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php mcp_disable_process ".$slaves[$j], 'w');
        }
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

    }

	// killlock
	killlock();
}

if($task == "mcp_disable_process")
{
	$ip_address = $argv[2];

	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo service cron stop; echo 'MCP Cluster disabled.' | sudo tee /dev/pts/0' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." MCP Cluster is disabled.");
	
	// killlock
	killlock();
}

## enable mcp cluster
if($task == "mcp_enable")
{
	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.mcp_enable.loc";
	if(file_exists($lockfile)){
		console_output("mcp_enable is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Turning on MCP Cluster");

	$nodes_file 			= @file_get_contents('/mcp_cluster/nodes.txt');
    $cluster['nodes'] 		= json_decode($nodes_file, TRUE);

   	$myip					= exec('sh /mcp_cluster/lan_ip.sh');

   	$cluster['total_master'] = 0;
    $cluster['total_slave'] = 0;
    foreach($cluster['nodes'] as $node)
    {
        if($node['type'] == 'master')
        {
            $cluster['total_master']++;
        }else{
            $cluster['total_slave']++;
            $cluster['slaves'][]['ip_address'] = $node['stats']['ip_address'];
        }
    }

   	foreach($cluster['slaves'] as $slave)
    {
    	if($slave['ip_address'] != $myip)
    	{
	    	$slaves[] = $slave['ip_address'];
	    }
    }

    $count 				= count($slaves);

    for ($i=0; $i<$runs; $i++) {
        for ($j=0; $j<$count; $j++) {
            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php mcp_enable_process ".$slaves[$j], 'w');
        }
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

    }

	// killlock
	killlock();
}

if($task == "mcp_enable_process")
{
	$ip_address = $argv[2];

	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo service cron start; echo 'MCP Cluster enabled.' | sudo tee /dev/pts/0' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." MCP Cluster is enabled.");
	
	// killlock
	killlock();
}
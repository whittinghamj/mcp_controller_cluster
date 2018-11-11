<?php
// MCP Controller Cluster - Cluster Management console Scripts

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

$api_url = 'http://dashboard.miningcontrolpanel.com';

$global_vars = '/etc/mcp/global_vars.php';
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
include('/mcp_cluster/functions.php');

function killlock(){
    global $lockfile;
	exec("rm -rf $lockfile");
}

$task = $argv[1];

if(isset($argv[3]))
{
	$silent = $argv[3];
}

$cluster = '';

if($task == "node_scanner")
{
	global $db;

	$data 					= get_system_stats();

	if($data['node_type'] == 'master')
	{
		$nodes 				= get_nodes();

		foreach($nodes as $node)
		{
			if($node['type'] == 'slave')
			{
				$ping_status = ping_node($node['ip_address']);
				if($ping_status == 'offline')
				{
					$result = $db->exec("UPDATE `nodes` SET `status` = 'offline' WHERE `id` = '".$node['id']."' ");

					console_output("Node: ".$node['ip_address']." is offline.");
				}else{
					console_output("Node: ".$node['ip_address']." is online.");
				}
			}
		}
	}
	// killlock
	killlock();
}

if($task == "node_checkin")
{
	global $db;

	$data 					= get_system_stats();

    if(empty($data['mac_address']))
    {
        console_output("MAC Address is empty, unable to continue.");
        die();
    }

    $does_node_exist        = does_node_exist($data['mac_address']);

    if($does_node_exist == 0)
    {
        $data               = get_system_stats();

        // cant find this node, lets get it added
        $insert = $db->exec("INSERT INTO `nodes` 
            (`updated`,`type`, `uptime`, `ip_address`, `ip_address_wan`, `mac_address`, `hardware`, `cpu_type`, `cpu_load`, `cpu_cores`, `cpu_temp`, `memory_usage`, `mcp_version`)
            VALUE
            ('".time()."','".$data['node_type']."', '".$data['uptime']."', '".$data['ip_address']."', '".$data['ip_address_wan']."', '".$data['mac_address']."', '".$data['hardware']."', '".$data['cpu_type']."','".$data['cpu_load']."', '".$data['cpu_cores']."', '".$data['cpu_temp']."', '".$data['memory_usage']."', '".$config['mcp_version']."' )");
        
        if (!$insert) {
            echo "\nPDO::errorInfo():\n";
            print_r($db->errorInfo());
        }

        $data['node_id'] = $db->lastInsertId(); 

        console_output("Node added to the cluster.");
    }else{
    	// existing node, update details
		$bits = get_node_details($data['mac_address']);

		$data['node_id'] = $bits['id'];

		$result = $db->exec("UPDATE `nodes` SET `updated` = '".time()."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `type` = '".$data['node_type']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `uptime` = '".$data['uptime']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `ip_address` = '".$data['ip_address']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `ip_address_wan` = '".$data['ip_address_wan']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_load` = '".$data['cpu_load']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `cpu_temp` = '".$data['cpu_temp']."' WHERE `id` = '".$data['node_id']."' ");
		$result = $db->exec("UPDATE `nodes` SET `memory_usage` = '".$data['memory_usage']."' WHERE `id` = '".$data['node_id']."' ");
        $result = $db->exec("UPDATE `nodes` SET `mcp_version` = '".$config['mcp_version']."' WHERE `id` = '".$data['node_id']."' ");

		$result = $db->exec("UPDATE `nodes` SET `status` = 'online' WHERE `id` = '".$data['node_id']."' ");

		console_output("Node stats updated.");
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

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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
	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo apt-get update' 2>/dev/null";
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

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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
	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo apt-get upgrade -y' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." upgrading OS.");
	
	// killlock
	killlock();
}

## update mcp cluster software for all slaves
if($task == "mcp_update")
{
	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.mcp_update.loc";
	if(file_exists($lockfile)){
		console_output("mcp_update is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Updating MCP Cluster Software");

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' AND `status` = 'online' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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
	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo sh /mcp_cluster/update.sh; sudo php -q /mcp_cluster/cluster.php node_checkin;' 2>/dev/null";
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

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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

	$cmd = "sshpass -pmcp scp -P 33077 /etc/mcp/global_vars.php mcp@".$ip_address.":/home/mcp/ 2>/dev/null";
	exec($cmd);

	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo mv /home/mcp/global_vars.php /mcp_cluster; echo 'Updating MCP Site Key'' 2>/dev/null";
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

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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

	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo service cron stop; sudo service apache2 stop;' 2>/dev/null";
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

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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

	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'sudo service cron start; sudo service apache2 start;' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." MCP Cluster is enabled.");
	
	// killlock
	killlock();
}

## run remote commands
if($task == "remote_command")
{
	$runs = 1;
	
	$lockfile = dirname(__FILE__) . "/cluster.mcp_enable.loc";
	if(file_exists($lockfile)){
		console_output("mcp_enable is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Running remote command on all nodes.");

	$query = $db->query("SELECT * FROM `nodes` WHERE `type` = 'slave' ");
	$cluster['nodes'] = $query->fetchAll(PDO::FETCH_ASSOC);

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
            $pipe[$j] = popen("php -q /mcp_cluster/cluster.php remote_command_process ".$slaves[$j], 'w');
        }
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

    }

	// killlock
	killlock();
}

if($task == "remote_command_process")
{
	$ip_address = $argv[2];

	$cmd = "sshpass -pmcp ssh -o StrictHostKeyChecking=no mcp@".$ip_address." -p 33077 'wget -N http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz -O /usr/share/GeoIP/GeoLiteCity.dat.gz && gunzip --force /usr/share/GeoIP/GeoLiteCity.dat.gz; ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat;' 2>/dev/null";
	exec($cmd);

	console_output("Node: ".$ip_address." remote package installed.");
	
	// killlock
	killlock();
}

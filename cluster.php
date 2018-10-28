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

if($task == "node_scanner")
{
	$lockfile = dirname(__FILE__) . "/console.cluster_scan.loc";
	if(file_exists($lockfile)){
		console_output("cluster_scan is already running. exiting");
		die();
	}else{
		exec("touch $lockfile");
	}
	
	console_output("Getting site IP ranges");

	$ip_ranges_raw = file_get_contents($api_url."/api/?key=".$config['api_key']."&c=site_ip_ranges");
	$ip_ranges = json_decode($ip_ranges_raw, true);

	foreach($ip_ranges['ip_ranges'] as $ip_range){
		$subnets[] = $ip_range['ip_range'];
	}

	// run multi threaded network scan for cluster nodes
	$ip_file = file('/mcp_cluster/node_ip_addresses.txt');

	// Loop through our array, show HTML source as HTML source; and line numbers too.
	foreach ($ip_file as $line_num => $line) {
	    $ips[] = $line;
	}

	print_r($ips, true);

	// killlock
	killlock();
}

if($task == "controller_checkin")
{
	$lockfile = dirname(__FILE__) . "/console.controller_checkin.loc";
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

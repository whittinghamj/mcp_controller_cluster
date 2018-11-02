<?php
// MCP Controller Cluster - Cluster Management console Scripts

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

include('/mcp_cluster/functions.php');

$task = $argv[1];

## detech usb key and copy new config file
if($task == "install_config_file")
{
	// set vars
	$usb_key = '/dev/sda1';
	$mount_point = '/mnt/mcp_key';
	$config_file = 'global_vars.php';

	// try and mount the usb key
	if(file_exists($usb_key))
	{
		// try and mount the usb key
		exec("sudo mount ".$usb_key." ".$mount_point);
	}

	// see if the config file exists
	if(file_exists($mount_point.'/'.$config_file))
	{
		// looks like the file is there, lets copy it
		exec("sudo cp ".$mount_point."/".$config_file." /etc/mcp/");
	}

	console_output("Copied new config file for MCP.");
}
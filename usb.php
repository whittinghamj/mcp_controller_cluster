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
	$usb_keys = array('/dev/sda1','/dev/sdb1','/dev/sdc1','/dev/sdd1','/dev/sde1','/dev/sdf1');
	$mount_point = '/mnt/mcp_key';
	$config_file = 'global_vars.php';

	// sanity checks
	if(!file_exists("/etc/mcp"))
	{
		exec("sudo mkdir /etc/mcp");
	}
	if(!file_exists("/mnt/mcp_key"))
	{
		exec("sudo mkdir /mnt/mcp_key");
	}

	// scan and try and mount the usb key
	foreach($usb_keys as $usb_key)
	{
		if(file_exists($usb_key))
		{
			// try and mount the usb key
			console_output("Attempting to mount MCP USB key.");

			exec("sudo mount ".$usb_key." ".$mount_point);

			break;
			$status = '';
		}else{
			$status = 'no_usb_found';
		}
	}

	if($status == 'no_usb_found')
	{
		console_output("MCP USB Keys not found.");
		fire_led('error');
		die();
	}

	// see if the config file exists
	if(file_exists($mount_point.'/'.$config_file))
	{
		// looks like the file is there, lets copy it
		console_output("Copying MCP Cluster confif file.");
		exec("sudo cp ".$mount_point."/".$config_file." /etc/mcp/");
	}else{
		console_output("MCP Cluster config file not found on USB key.");
		fire_led('error');
		die();
	}

	exec("sudo umount /dev/sda1 > /dev/null 2>&1");

	console_output("Copied new config file for MCP.");
	fire_led('success');
}
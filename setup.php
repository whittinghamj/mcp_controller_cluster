<?php
// MCP Controller Cluster - Cluster Management console Scripts

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

include('/mcp_cluster/functions.php');

// $task = $argv[1];

// sanity checks
if(file_exists("/etc/mcp/global_vars.php"))
{
	console_output("Cluster is already configured.");
	die();
}

$master_ip_address 		= gethostbyname('cluster-master');

$config_data			= file_get_contents("http://".$master_ip_address.":1372/web_api.php?c=cluster_configuration");
$config 				= json_decode($config_data, true);

$config_file '<?php $arr = ' . var_export($config, true) . ';';

file_put_contents('/etc/mcp/global_vars.php', $config_file);

console_output("MCP Cluster is now configured.");
// fire_led('success');
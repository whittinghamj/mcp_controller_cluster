<?php
// MCP Controller Cluster - Cluster Management console Scripts

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

include('/mcp_cluster/functions.php');


$data 					= get_system_stats();

if($data['node_type'] == 'slave')
{
	if(file_exists("/etc/mcp/global_vars.php"))
	{
		console_output("Cluster is already configured.");
		die();
	}

	$master_ip_address 		= gethostbyname('cluster-master');

	$config_data			= file_get_contents("http://".$master_ip_address.":1372/web_api.php?c=cluster_configuration");
	$config 				= json_decode($config_data, true);

	exec("echo '<?php' > /etc/mcp/global_vars.php");
	exec("echo '' >> /etc/mcp/global_vars.php");
	exec("echo '' >> /etc/mcp/global_vars.php");
	exec('echo $config["api_key"] = "'.$config['api_key'].'"; >> /etc/mcp/global_vars.php');
	exec("echo '' >> /etc/mcp/global_vars.php");
	exec('echo $config["master"] = "'.$config['master_ip_address'].'"; >> /etc/mcp/global_vars.php');

	console_output("MCP Cluster is now configured.");
	// fire_led('success');
}else{
	console_output("MCP Cluster Master cannot run this file.");
}
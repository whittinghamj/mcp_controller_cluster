<?php

// MCP Cluster - Master Reset Script

include('/mcp_cluster/db.php');

echo "MCP Cluster - Master Reset \n";
echo "\n";
echo "You are about to reset this master node to a factory default state. \n";
echo "Your cluster will no longer be monitoring your miners with MCP until \n";
echo "you reconfigure a master node. \n";
echo "\n";
echo "Are you sure you want to do this?  Type 'yes' to continue: ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim($line) != 'yes'){
    echo "ABORTING!\n";
    exit;
}
fclose($handle);
echo "\n"; 
echo "Resetting MCP Cluster Master node...\n";

$default_config = '{"api_key":"","master":""}';

$remove_nodes = $db->query("TRUNCATE TABLE `nodes`");
$remove_miners = $db->query("TRUNCATE TABLE `nodes`");

file_put_contents('/etc/mcp/global_vars.php', $default_config);

echo "Reset complete. \n";
echo "\n";
echo "Go to http://".$config['master'].":1372 to configure this master node.\n";

<?php

// MCP Cluster - Slave Reset Script

include('/mcp_cluster/db.php');

echo "MCP Cluster - Slave Reset \n";
echo "\n";
echo "You are about to reset this slave node to a factory default state. \n";
echo "This slave node will be removed from the cluster and it will auto rejoin \n";
echo "in a few minutes unless powered down. \n";
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
echo "Resetting MCP Cluster Slave node...\n";

$default_config 		= '{"api_key":"","master":""}';

$mac_address            = strtoupper(exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address"));

$remove_node 			= $db->query("DELETE FROM `nodes` WHERE `mac_address` = '".$mac_address."' ");

file_put_contents('/etc/mcp/global_vars.php', $default_config);

echo "Reset complete. \n";
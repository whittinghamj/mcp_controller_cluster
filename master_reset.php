<?php

// MCP Cluster - Master Reset Script

{"api_key":"279e8017e2d2d0d7d591a25e40d6cada","master":"192.168.1.240"}

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
echo "Thank you, continuing...\n";
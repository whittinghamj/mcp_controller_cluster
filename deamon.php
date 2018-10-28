<?php

// version 1.3

// check to see if we have any nodes available
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
    }
}

$cluster['total_nodes'] = count($cluster['nodes']);


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

console_output("MCP Controller Cluster");

console_output("Total Nodes: ".$cluster['total_nodes']);
console_output(" - Master: ".$cluster['total_master']);
console_output(" - Slaves: ".$cluster['total_slave']);

// sleep(30);

// set $cluster vars
$hostname               = exec('cat /etc/hostname');
if($hostname == 'cluster-master')
{
    $cluster['machine']['type'] = 'master';
}else{
    $cluster['machine']['type'] = 'node';
}
$cluster['machine']['ip_address'] = exec('sh /mcp_cluster/lan_ip.sh');



$runs                   = $argv[1];
$forced_lag             = $argv[2];
$forced_lag_counter     = 0;

$miners_raw 		= file_get_contents($api_url."/api/?key=".$config['api_key']."&c=site_miners");
$miners 			= json_decode($miners_raw, true);

if(isset($miners['miners']))
{
    foreach($miners['miners'] as $miner)
    {
    	$miner_ids[] = $miner['id'];
    }

    $count 				= count($miner_ids);

    console_output("Polling " . $count . " miners.");

    console_output("Stopped for dev.");
    die();

    for ($i=0; $i<$runs; $i++) {
        console_output("Spawning children.");
        for ($j=0; $j<$count; $j++) {
        	// echo "Checking Miner: ".$miner_ids[$j]."\n";

            $pipe[$j] = popen("php -q /mcp/deamon_update_miner_stats.php -p='".$miner_ids[$j]."'", 'w');

            if(isset($argv[2]))
            {
                $forced_lag_counter = $forced_lag_counter + 1;
                // console_output($forced_lag_counter);
                if($forced_lag_counter == $forced_lag)
                {
                    // console_output("forced_lag_counter = " . $forced_lag_counter);
                    sleep(1);
                    // console_output("done sleeping");
                    $forced_lag_counter = 0;
                }
            }
        }

        // console_output("Killing children.");
        
        // wait for them to finish
        for ($j=0; $j<$count; ++$j) {
            pclose($pipe[$j]);
        }

        // console_output("Sleeping.");
        // sleep(1);
    }
}else{
    console_output("No ASIC miners.");
}

exit();

?>
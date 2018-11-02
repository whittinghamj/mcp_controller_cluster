<?php

include('/mcp_cluster/db.php');

// check if this is a master or slave node
$node_type               = exec('cat /etc/hostname');
if($node_type == 'cluster-master')
{
    $this_node['type'] = 'master';
}else{
    $this_node['type'] = 'slave';
}

if($this_node['type'] == 'master')
{
    // check to see if we have any nodes available
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
    $cluster['total_nodes'] = count($cluster['nodes']);

    // set main api url endpoint
    $api_url = 'http://dashboard.miningcontrolpanel.com';

    // sanity check
    $global_vars = '/mcp_cluster/global_vars.php';
    if(!file_exists($global_vars))
    {
        echo $global_vars . " is missing. git clone could be in progress. \n";
        die();
    }

    // sanity check
    $functions = '/mcp_cluster/functions.php';
    if(!file_exists($functions))
    {
        echo $functions . " is missing. git clone could be in progress. \n";
        die();
    }

    // includes
    include('/mcp_cluster/global_vars.php');
    include('/mcp_cluster/functions.php');

    // output
    console_output("MCP Controller Cluster - Master");
    console_output("Total Nodes: ".$cluster['total_nodes']);
    console_output(" >- Master: ".$cluster['total_master']);
    console_output(" >- Slaves: ".$cluster['total_slave']);

    if($cluster['total_slave'] == 0)
    {
        console_output("No slaves available, exiting.");
        die();
    }

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

        // count total miners to process
        $total_miners 				= count($miner_ids);
        console_output("Total Miners: " . $total_miners);

        // calculate how many jobs per slave node
        $jobs_per_node = round($total_miners / $cluster['total_slave']);
        console_output("Jobs Per Slave: " . $jobs_per_node);

        $query = $db->query("TRUNCATE TABLE `miners` ");

        foreach($miner_ids as $miner_id)
        {
            $result = $db->exec("INSERT INTO `miners` 
                (`updated`,`miner_id`, `node_id`)
                VALUE
                ('".time()."','".$miner_id."', '".rand(1,$total_nodes)."')");
        }
    }else{
        console_output("No ASIC miners.");
    }
}

if($this_node['type'] == 'slave')
{
    // set main api url endpoint
    $api_url = 'http://dashboard.miningcontrolpanel.com';

    // sanity check
    $global_vars = '/mcp_cluster/global_vars.php';
    if(!file_exists($global_vars))
    {
        echo $global_vars . " is missing. git clone could be in progress. \n";
        die();
    }

    // sanity check
    $functions = '/mcp_cluster/functions.php';
    if(!file_exists($functions))
    {
        echo $functions . " is missing. git clone could be in progress. \n";
        die();
    }

    // includes
    include('/mcp_cluster/global_vars.php');
    include('/mcp_cluster/functions.php');

    // output
    console_output("MCP Controller Cluster - Slave");

    $runs                   = $argv[1];
    $forced_lag             = $argv[2];
    $forced_lag_counter     = 0;

    // sanity check
    $miner_ids_file = '/var/www/html/ids.txt';
    if(!file_exists($miner_ids_file))
    {
        console_output("Cluster is currently building its matrix, try again.");
        die();
    }

    $miners_raw         = file_get_contents('/var/www/html/ids.txt');
    $miner_ids          = json_decode($miners_raw, true);

    $count              = count($miner_ids);

    if(is_array($miner_ids))
    {

        for ($i=0; $i<$runs; $i++) {
            console_output("Spawning children.");
            for ($j=0; $j<$count; $j++) {
                // echo "Checking Miner: ".$miner_ids[$j]."\n";

                $pipe[$j] = popen("php -q /mcp_cluster/deamon_update_miner_stats.php -p='".$miner_ids[$j]."'", 'w');

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
}

exit();

?>
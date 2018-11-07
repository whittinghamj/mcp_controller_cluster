<?php
// MCP Controller Cluster - Find master and build local config file

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

$data['hostname']               = exec('cat /etc/hostname');

if($data['hostname'] == 'cluster-master')
{
    $data['node_type']          = 'master';
}else{
    $data['node_type']          = 'slave';
}

if($data['node_type'] == 'slave')
{
	// get local ip establish subnet
	$my_ip = exec('sh /mcp_cluster/lan_ip.sh');
	$my_ip_bits = explode('.', $my_ip);

	// scan my local subnet for cluster nodes
	exec('rm -rf /mcp_cluster/node_ip_addresses.txt && touch /mcp_cluster/node_ip_addresses.txt');
	exec('nmap -p1372 "'.$my_ip_bits[0].'.'.$my_ip_bits[1].'.'.$my_ip_bits[2].'.0/24" -oG - | grep 1372/open | awk \'{ print $2 }\' >> /mcp_cluster/node_ip_addresses.txt');

	$nodes = file('/mcp_cluster/node_ip_addresses.txt');

	// print_r($nodes);

	// This loop creates a new fork for each of the items in $tasks.
	foreach($nodes as $node)
	{
		$node 						= str_replace(' ', '', $node);
		$node 						= trim($node, " \t.");
		$node 						= trim($node, " \n.");
		$node 						= trim($node, " \r.");

		$pid = pcntl_fork();
		if ($pid == -1)
		{
			exit("Error forking...\n");
		}elseif($pid == 0){
			execute_task($node);
			exit();
		}
	}

	// This while loop holds the parent process until all the child threads
	// are complete - at which point the script continues to execute.
	while(pcntl_waitpid(0, $status) != -1);

	// You could have more code here.
	echo "Done \n";

	/**
	 * Helper method to execute a task.
	 */
	function execute_task($ip_address)
	{
		// echo "Checking: '${ip_address}'\n";
		// Simulate doing actual work with sleep().
		// $execution_time = rand(5, 10);
		// sleep($execution_time);

		$remote_content 	= @file_get_contents("http://".$ip_address.":1372/web_api.php?c=cluster_configuration");
		$remote_data		= json_decode($remote_content, true);

		if(is_array($remote_data))
		{
			if(isset($remote_data['node_type']) && $remote_data['node_type'] == 'master')
			{
				echo "MCP Cluster Master found on " . $ip_address."\n";

				$api_key = $remote_data['api_key'];
				$master_ip_address = $remote_data['master_ip_address'];

				$data['api_key'] = $remote_data['api_key'];
				$data['master'] = $remote_data['master_ip_address'];

				$json = json_encode($data, true);

				file_put_contents('/etc/mcp/global_vars.php', $json);
				
			}
		}
		// echo "Completed task: ${task_id}. Took ${execution_time} seconds.\n";
	}
}else{
	console_output("MCP Cluster Master cannot run this file.");
}
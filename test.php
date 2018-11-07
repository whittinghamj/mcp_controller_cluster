<?php
/**
 * @file
 * Basic demonstration of how to do parallel threads in PHP.
 */
// This array of "tasks" could be anything. For demonstration purposes
// these are just strings, but they could be a callback, class or
// include file (hell, even code-as-a-string to pass to eval()).
$tasks = [
  "192.168.1.240",
  "192.168.1.241",
  "192.168.1.242",
  "192.168.3.136",
  "192.168.7.50",
];

// This loop creates a new fork for each of the items in $tasks.
foreach($tasks as $task)
{
	$pid = pcntl_fork();
	if ($pid == -1)
	{
		exit("Error forking...\n");
	}elseif($pid == 0){
		execute_task($task);
		exit();
	}
}

// This while loop holds the parent process until all the child threads
// are complete - at which point the script continues to execute.
while(pcntl_waitpid(0, $status) != -1);

// You could have more code here.
echo "Do stuff after all parallel execution is complete.\n";

/**
 * Helper method to execute a task.
 */
function execute_task($ip_address)
{
	echo "Checking: ${ip_address}\n";
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
		}
	}
	// echo "Completed task: ${task_id}. Took ${execution_time} seconds.\n";
}
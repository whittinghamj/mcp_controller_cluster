<?php
/**
 * @file
 * Basic demonstration of how to do parallel threads in PHP.
 */
// This array of "tasks" could be anything. For demonstration purposes
// these are just strings, but they could be a callback, class or
// include file (hell, even code-as-a-string to pass to eval()).
$tasks = [
  "fetch_remote_data",
  "post_async_updates",
  "clear_caches",
  "notify_admin",
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
function execute_task($task_id)
{
	echo "Starting task: ${task_id}\n";
	// Simulate doing actual work with sleep().
	$execution_time = rand(5, 10);
	sleep($execution_time);
	echo "Completed task: ${task_id}. Took ${execution_time} seconds.\n";
}
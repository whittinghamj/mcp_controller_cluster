<?php
/**
 * @file
 * Basic demonstration of how to do parallel threads in PHP.
 */
// This array of "tasks" could be anything. For demonstration purposes
// these are just strings, but they could be a callback, class or
// include file (hell, even code-as-a-string to pass to eval()).

// scan my local subnet for cluster nodes
exec('rm -rf /mcp_cluster/mcp_cluster/node_ip_addresses.txt && touch /mcp_cluster/mcp_cluster/node_ip_addresses.txt');
exec('nmap -p1372 "192.168.1.0/24" -oG - | grep 1372/open | awk \'{ print $2 }\' >> /mcp_cluster/node_ip_addresses.txt');

$nodes 		= @file_get_contents('/mcp_cluster/node_ip_addresses.txt');

print_r($nodes);

die();

$tasks = [
	"192.168.1.240",
  	"192.168.1.241",
  	"192.168.1.242",
  	"192.168.3.136",
  	"192.168.7.50",
  	"192.168.7.155",
  	"192.168.7.149",
  	"192.168.7.163
192.168.7.105
192.168.7.178",
  	"192.168.7.180",
  	"192.168.7.181",
  	"192.168.7.203",
  	"192.168.7.174",
  	"192.168.7.165",
  	"192.168.7.154",
  	"192.168.7.108",
  	"192.168.7.129",
  	"192.168.7.124",
  	"192.168.7.173",
  	"192.168.7.137",
  	"192.168.7.107",
  	"192.168.7.73",
  	"192.168.7.191",
  	"192.168.7.65",
  	"192.168.7.169",
  	"192.168.7.102",
  	"192.168.7.202",
  	"192.168.7.187",
  	"192.168.7.125",
  	"192.168.7.160",
  	"192.168.7.171",
  	"192.168.7.89",
  	"192.168.7.76
192.168.7.193
192.168.7.55
192.168.7.54
192.168.7.162
192.168.7.146
192.168.7.70
192.168.7.100
192.168.7.144
192.168.7.170
192.168.7.106
192.168.7.114
192.168.7.150
192.168.7.75
192.168.7.159
192.168.7.74
192.168.7.81
192.168.7.101
192.168.7.126
192.168.7.176
192.168.7.104
192.168.7.92
192.168.7.77
192.168.7.52
192.168.7.90
192.168.7.147
192.168.7.86
192.168.7.153
192.168.7.131
192.168.7.82
192.168.7.148
192.168.7.205
192.168.7.118
192.168.7.64
192.168.7.182
192.168.7.190
192.168.7.53
192.168.7.68
192.168.7.130
192.168.7.95
192.168.7.84
192.168.7.179
192.168.7.62
192.168.7.93
192.168.7.109
192.168.7.208
192.168.7.166
192.168.7.141
192.168.7.201
192.168.7.185
192.168.7.199
192.168.7.117
192.168.7.152
192.168.7.111
192.168.7.85
192.168.7.209
192.168.7.96
192.168.7.200
192.168.7.1
192.168.7.151
192.168.7.79
192.168.7.207
192.168.7.122
192.168.7.132
192.168.7.69
192.168.7.66
192.168.7.156
192.168.7.61
192.168.7.197
192.168.7.94
192.168.7.51
192.168.7.120
192.168.7.145
192.168.7.157
192.168.7.80
192.168.7.67
192.168.7.143
192.168.7.183
192.168.7.177
192.168.7.87
192.168.7.140
192.168.7.184
192.168.7.164
192.168.7.167
192.168.7.128
192.168.7.121
192.168.7.63
192.168.7.195
192.168.7.196
192.168.7.57
192.168.7.158
192.168.7.172
192.168.7.119
192.168.7.142
192.168.7.139
192.168.7.133
192.168.7.123
192.168.7.56
192.168.7.138
192.168.7.186
192.168.7.189
192.168.7.103
192.168.7.99
192.168.7.194
192.168.7.58
192.168.7.161
192.168.7.204
192.168.7.206
192.168.7.135
192.168.7.110
192.168.7.254
192.168.7.98
192.168.7.175
192.168.7.188
192.168.7.112
192.168.7.72
192.168.7.78
192.168.7.115
192.168.7.168
192.168.7.71
192.168.7.134
192.168.7.91
192.168.7.88
192.168.7.198
192.168.7.127
192.168.7.192
192.168.7.210
192.168.7.60
192.168.7.83
192.168.7.136
192.168.7.97
192.168.7.59
192.168.7.113
192.168.7.116

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
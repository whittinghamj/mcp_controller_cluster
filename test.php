<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL); 

// reset from last run
exec("kill $(ps aux | grep 'cluster_workload_with_cpu_load.py' | awk '{print $2}')");
exec("python /mcp_cluster/reset_blinkt.py");

// set vars
$time_to_run = 60;

$global_vars = '/etc/mcp/global_vars.php';
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

include('/mcp_cluster/db.php');
include('/mcp_cluster/functions.php');

foreach(range(0, 58) as $time)
{
	$data['cpu_cores']              = system_cores();
	$data['cpu_load']               = exec('ps -A -o pcpu | tail -n+2 | paste -sd+ | bc');
	$data['cpu_load']               = number_format($data['cpu_load'] / $data['cpu_cores'], 2);

	if($data['cpu_load'] >= 0)
	{
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 0 255 0');
	}elseif($data['cpu_load'] >=11){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 56 255 0');
	}elseif($data['cpu_load'] >=21){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 113 255 0');
	}elseif($data['cpu_load'] >=31){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 170 255 0');
	}elseif($data['cpu_load'] >=41){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 226 255 0');
	}elseif($data['cpu_load'] >=51){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 226 0');
	}elseif($data['cpu_load'] >=61){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 170 0');
	}elseif($data['cpu_load'] >=71){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 113 0');
	}elseif($data['cpu_load'] >=81){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 56 0');
	}elseif($data['cpu_load'] >=91){
		exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 0 0');
	}
	sleep(1);
}
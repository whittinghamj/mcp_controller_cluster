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

	console_output("CPU Load: ".$data['cpu_load']);

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
	);

	if($data['cpu_load'] >= 0 && $data['cpu_load'] < 10)
	{
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 0 255 0');
	}elseif($data['cpu_load'] >=11 && $data['cpu_load'] < 20){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 56 255 0');
	}elseif($data['cpu_load'] >=21 && $data['cpu_load'] < 30){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 113 255 0');
	}elseif($data['cpu_load'] >=31 && $data['cpu_load'] < 40){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 170 255 0');
	}elseif($data['cpu_load'] >=41 && $data['cpu_load'] < 50){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 226 255 0');
	}elseif($data['cpu_load'] >=51 && $data['cpu_load'] < 60){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 226 0');
	}elseif($data['cpu_load'] >=61 && $data['cpu_load'] < 70){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 170 0');
	}elseif($data['cpu_load'] >=71 && $data['cpu_load'] < 80){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 113 0');
	}elseif($data['cpu_load'] >=81 && $data['cpu_load'] < 90){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 56 0');
	}elseif($data['cpu_load'] >=91){
		$proc = exec('python /mcp_cluster/cluster_workload_with_cpu_load.py 255 0 0');
	}

	// run the non-blocking process
    stream_set_blocking($proc, 0);

    // sleep for 1 second
	sleep(1);
}
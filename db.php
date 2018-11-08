<?php

// include('/etc/mcp/global_vars.php');
$config_file		= @file_get_contents('/etc/mcp/global_vars.php');
$config 			= json_decode($config_file, true);

// check is this node is a master or a slave
$data['hostname']               = exec('cat /etc/hostname');
if($data['hostname'] == 'cluster-master')
{
    $data['node_type']          = 'master';
    $database['hostname']		= 'localhost';
}else{
    $data['node_type']          = 'slave';
    $database['hostname']		= $config['master'];
}

////////////////////////////////////////////////////////////////////////////////////////////////////
// mysql settings
$database['username']		= "root";
$database['password']		= "admin1372";
$database['database']		= "mcp_cluster";
$database['hostname']		= $config['master'];
////////////////////////////////////////////////////////////////////////////////////////////////////
// mysql connection

$db = new PDO('mysql:host='.$database['hostname'].';dbname='.$database['database'].';charset=utf8mb4', $database['username'], $database['password']);
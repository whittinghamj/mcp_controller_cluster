<?php

include('/etc/mcp/global_vars.php');

////////////////////////////////////////////////////////////////////////////////////////////////////
// mysql settings
$database['username']	= "root";
$database['password']	= "admin1372";
$database['database']	= "mcp_cluster";
$database['hostname']	= $config['master'];
////////////////////////////////////////////////////////////////////////////////////////////////////
// mysql connection

$db = new PDO('mysql:host='.$database['hostname'].';dbname='.$database['database'].';charset=utf8mb4', $database['username'], $database['password']);
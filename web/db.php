<?php

////////////////////////////////////////////////////////////////////////////////////////////////////
// mysql settings
$database['username']	= "root";
$database['password']	= "admin1372";
$database['database']	= "mcp_cluster";
$database['hostname']	= "192.168.3.136";
////////////////////////////////////////////////////////////////////////////////////////////////////
// mysql connection

$db = new PDO('mysql:host='.$database['hostname'].';dbname='.$database['database'].';charset=utf8mb4', $database['username'], $database['password']);
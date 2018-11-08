<?php

ini_set('session.gc_maxlifetime', 86400);

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

session_start();

// include("inc/global_vars.php");
// include("inc/functions.php");

$a = $_GET['a'];

switch ($a)
{
	case "test":
		test();
		break;
	
	case "update_api_key":
		update_api_key();
		break;
			
// default
				
	default:
		home();
		break;
}

function home(){
	die('access denied to function name ' . $_GET['a']);
}

function test(){
	echo '<h3>$_SESSION</h3>';
	echo '<pre>';
	print_r($_SESSION);
	echo '</pre>';
	echo '<hr>';
	echo '<h3>$_POST</h3>';
	echo '<pre>';
	print_r($_POST);
	echo '</pre>';
	echo '<hr>';
	echo '<h3>$_GET</h3>';
	echo '<pre>';
	print_r($_GET);
	echo '</pre>';
	echo '<hr>';
}

function set_status_message(){
	$status 				= $_GET['status'];
	$message			= $_GET['message'];
	
	status_message($status, $message);
}

function update_api_key()
{
	$data['api_key'] 	= $_GET['api_key'];
	$data['api_key']	= str_replace(' ', '', $data['api_key']);

	if(empty($data['api_key']))
	{
		// set_status_message('danger', 'API Key cannot be empty.');
	}elseif(!ctype_alnum($data['api_key'])){
        // set_status_message('danger', 'The API Key does not appear to be valid.');
	}else{
		$data['master'] 	= exec("sh /mcp_cluster/lan_ip.sh");

		$json = json_encode($remote_data, true);

		file_put_contents('/etc/mcp/global_vars.php', $json);

		// set_status_message('success','Your API Key has been saved.');
	}

	go($_SERVER['HTTP_REFERER']);
}
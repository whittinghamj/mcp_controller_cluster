<?php

function c_to_f($temp)
{
    $fahrenheit=$temp*9/5+32;
    return $fahrenheit ;
}

function console_output($data)
{
	$timestamp = date("Y-m-d H:i:s", time());
	echo "[" . $timestamp . "] - " . $data . "\n";
}

function json_output($data)
{
	$data['timestamp']		= time();
	$data 					= json_encode($data);
	echo $data;
	die();
}

function getsock($addr, $port)
{
	$socket = null;
 	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
 	if ($socket === false || $socket === null)
 	{
    	$error = socket_strerror(socket_last_error());
    	$msg = "socket create(TCP) failed";
    	// echo "ERR: $msg '$error'\n";
    	return null;
 	}

 	$res = @socket_connect($socket, $addr, $port);
 	if ($res === false)
 	{
    	$error = socket_strerror(socket_last_error());
    	$msg = "socket connect($addr,$port) failed";
    	// echo "ERR: $msg '$error'\n";
    	socket_close($socket);
    	return null;
 	}
 	return $socket;
}

function readsockline($socket)
{
	$line = '';
	while (true)
	{
    	$byte = socket_read($socket, 1);
    	if ($byte === false || $byte === '')
        	break;
    	if ($byte === "\0")
        	break;
    	$line .= $byte;
	}
 	return $line;
}

function request($ip, $cmd)
{
 $socket = getsock($ip, 4028);
 if ($socket != null)
 {
    socket_write($socket, $cmd, strlen($cmd));
    $line = readsockline($socket);
    socket_close($socket);

    if (strlen($line) == 0)
    {
        echo "WARN: '$cmd' returned nothing\n";
        return $line;
    }

    // print "$cmd returned '$line'\n";

    if (substr($line,0,1) == '{')
        return json_decode($line, true);

    $data = array();

    $objs = explode('|', $line);
    foreach ($objs as $obj)
    {
        if (strlen($obj) > 0)
        {
            $items = explode(',', $obj);
            $item = $items[0];
            $id = explode('=', $items[0], 2);
            if (count($id) == 1 or !ctype_digit($id[1]))
                $name = $id[0];
            else
                $name = $id[0].$id[1];

            if (strlen($name) == 0)
                $name = 'null';

            if (isset($data[$name]))
            {
                $num = 1;
                while (isset($data[$name.$num]))
                    $num++;
                $name .= $num;
            }

            $counter = 0;
            foreach ($items as $item)
            {
                $id = explode('=', $item, 2);
                if (count($id) == 2)
                    $data[$name][$id[0]] = $id[1];
                else
                    $data[$name][$counter] = $id[0];

                $counter++;
            }
        }
    }

    return $data;
 }

 return null;
}

function ping($ip)
{
    $pingresult = exec("/bin/ping -c2 -w2 $ip", $outcome, $status);  
    if ($status==0) {
    	$status = "alive";
    } else {
    	$status = "dead";
    }
    return $status;
}

function cidr_to_range($cidr)
{
  	$range = array();
  	$cidr = explode('/', $cidr);
  	$range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
  	$range[1] = long2ip((ip2long($cidr[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
  	return $range;
}

function percentage($val1, $val2, $precision)
{
	$division = $val1 / $val2;
	$res = $division * 100;
	$res = round($res, $precision);
	return $res;
}

function clean_string($value)
{
    if ( get_magic_quotes_gpc() ){
         $value = stripslashes( $value );
    }
	// $value = str_replace('%','',$value);
    return mysql_real_escape_string($value);
}

function go($link = '')
{
	header("Location: " . $link);
	die();
}

function url($url = '')
{
	$host = $_SERVER['HTTP_HOST'];
	$host = !preg_match('/^http/', $host) ? 'http://' . $host : $host;
	$path = preg_replace('/\w+\.php/', '', $_SERVER['REQUEST_URI']);
	$path = preg_replace('/\?.*$/', '', $path);
	$path = !preg_match('/\/$/', $path) ? $path . '/' : $path;
	if ( preg_match('/http:/', $host) && is_ssl() ) {
		$host = preg_replace('/http:/', 'https:', $host);
	}
	if ( preg_match('/https:/', $host) && !is_ssl() ) {
		$host = preg_replace('/https:/', 'http:', $host);
	}
	return $host . $path . $url;
}

function post($key = null)
{
	if ( is_null($key) ) {
		return $_POST;
	}
	$post = isset($_POST[$key]) ? $_POST[$key] : null;
	if ( is_string($post) ) {
		$post = trim($post);
	}
	return $post;
}

function get($key = null)
{
	if ( is_null($key) ) {
		return $_GET;
	}
	$get = isset($_GET[$key]) ? $_GET[$key] : null;
	if ( is_string($get) ) {
		$get = trim($get);
	}
	return $get;
}

function debug($input)
{
	$output = '<pre>';
	if ( is_array($input) || is_object($input) ) {
		$output .= print_r($input, true);
	} else {
		$output .= $input;
	}
	$output .= '</pre>';
	echo $output;
}

function debug_die($input)
{
	die(debug($input));
}

function status_message($status, $message)
{
	$_SESSION['alert']['status']			= $status;
	$_SESSION['alert']['message']		= $message;
}

function call_remote_content($url)
{
	echo file_get_contents($url);
}
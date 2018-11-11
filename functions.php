<?php

function geoip($ip_address)
{
    $location               = geoip_record_by_name($ip_address);

    return $location;
}

function post_to_slave($postdata, $ip_address)
{
    $poststring = json_encode($postdata);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://'.$ip_address.':1372/web_api.php?c=process_miners');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSLVERSION,3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $poststring);
    $data = curl_exec($ch);
    curl_close($ch);

    $results = json_decode($data, true);

    return $results;
}

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

// convert array to json
function json_output($data)
{
    $data['timestamp']      = time();
    $data                   = json_encode($data);
    echo $data;
    die();
}

// get number of system cores
function system_cores()
{
    $cmd = "uname";
    $OS = strtolower(trim(shell_exec($cmd)));
 
    switch($OS) {
       case('linux'):
          $cmd = "cat /proc/cpuinfo | grep processor | wc -l";
          break;
       case('freebsd'):
          $cmd = "sysctl -a | grep 'hw.ncpu' | cut -d ':' -f2";
          break;
       default:
          unset($cmd);
    }
 
    if ($cmd != '') {
       $cpuCoreNo = intval(trim(shell_exec($cmd)));
    }
    
    return empty($cpuCoreNo) ? 1 : $cpuCoreNo;
}

// get cpu load
function cpu_load($coreCount = 4, $interval = 1)
{
    $stat1 = file('/proc/stat'); 
    sleep(1); 
    $stat2 = file('/proc/stat'); 
    $info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0])); 
    $info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0])); 
    $dif = array(); 
    $dif['user'] = $info2[0] - $info1[0]; 
    $dif['nice'] = $info2[1] - $info1[1]; 
    $dif['sys'] = $info2[2] - $info1[2]; 
    $dif['idle'] = $info2[3] - $info1[3]; 
    $total = array_sum($dif); 
    $cpu = array(); 
    foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);

    return $cpu['sys'];
}

// get memory usage
function system_memory_usage()
{
    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2] / $mem[1] * 100;
 
    return $memory_usage;
}

// system uptime
function system_uptime()
{
    $uptime = exec('uptime -p');

    $uptime = str_replace("up ", "", $uptime);

    $uptime = str_replace(" minutes", "m", $uptime);

    $uptime = str_replace(" hours", "h", $uptime);
    $uptime = str_replace(" hour", "h", $uptime);

    $uptime = str_replace(" days", "d", $uptime);
    
    return $uptime;
}

function percentage($val1, $val2, $precision = 2)
{
    $division = $val1 / $val2;
    $res = $division * 100;
    $res = round($res, $precision);
    return $res;
}

function does_node_exist($mac_address)
{
    global $db;
    
    $query = $db->query("SELECT `id` FROM `nodes` WHERE `mac_address` = '".$mac_address."' ");
    $total = $query->rowCount();
    
    return $total;
}

function get_node_details($mac_address)
{
    global $db;

    $query = $db->query("SELECT * FROM `nodes` WHERE `mac_address` = '".$mac_address."'");
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    if(isset($data[0]))
    {
        $data[0]['node_id']                 = $data[0]['id'];

        $location                           = geoip_record_by_name($data[0]['ip_address_wan']);
        
        $data[0]['location']                = $location;
        
        return $data[0];
    }else{
        return $data;
    }
}

function get_nodes()
{
    global $db;

    $query = $db->query("SELECT * FROM `nodes` ORDER BY `type`,INET_ATON(ip_address)");
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach($data as $node)
    {
        $nodes[$count]                   = $node;
        $nodes[$count]['node_id']        = $node['id'];

        $location                        = geoip_record_by_name($node['ip_address_wan']);
    
        $nodes[$count]['location']       = $location;

        $node['hardware']               = str_replace(' Rev 1.0', '', $node['hardware']);
        $node['hardware']               = str_replace(' Rev 1.1', '', $node['hardware']);
        $node['hardware']               = str_replace(' Rev 1.2', '', $node['hardware']);
        $node['hardware']               = str_replace(' Rev 1.3', '', $node['hardware']);
        $node['hardware']               = str_replace(' Model ', '', $node['hardware']);
        $node['hardware']               = str_replace('Raspberry ', 'R-', $node['hardware']);
        $node['hardware']               = str_replace(' Plus', '+', $node['hardware']);
        $nodes[$count]['hardware']      = $node['hardware'];

        $count++;
    }

    return $nodes;
}

function fire_led($status)
{
    // clear the LED
    exec('echo 0 >/sys/class/leds/led0/brightness');

    // display success pulses
    if($status == 'success')
    {
        exec('echo 1 >/sys/class/leds/led0/brightness');
        sleep(1);
        exec('echo 0 >/sys/class/leds/led0/brightness');
        sleep(1);
        exec('echo 1 >/sys/class/leds/led0/brightness');
        sleep(1);
        exec('echo 0 >/sys/class/leds/led0/brightness');
        sleep(1);
        exec('echo 1 >/sys/class/leds/led0/brightness');
        sleep(1);
        exec('echo 0 >/sys/class/leds/led0/brightness');        
    }

    if($status == 'error')
    {
        exec('echo 1 >/sys/class/leds/led0/brightness');
        sleep(3);
        exec('echo 0 >/sys/class/leds/led0/brightness');
        sleep(1);
        exec('echo 1 >/sys/class/leds/led0/brightness');
        sleep(3);
        exec('echo 0 >/sys/class/leds/led0/brightness');
        sleep(1);
        exec('echo 1 >/sys/class/leds/led0/brightness');
        sleep(3);
        exec('echo 0 >/sys/class/leds/led0/brightness');
    }
}

function get_system_stats()
{
    $data['cpu_type']               = exec("sed -n 's/^model name[ \t]*: *//p' /proc/cpuinfo | head -n 1");
    $data['cpu_cores']              = system_cores();
    $data['cpu_load']               = exec('ps -A -o pcpu | tail -n+2 | paste -sd+ | bc');
    $data['cpu_load']               = number_format($data['cpu_load'] / $data['cpu_cores'], 2);
    $data['cpu_temp']               = number_format(exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000, 2);
    $data['memory_usage']           = system_memory_usage();
    $data['hdd_usage']              = exec('echo `df -lh | awk \'{if ($6 == "/") { print $5 }}\' | head -1 | cut -d\'%\' -f1`');
    $data['uptime']                 = system_uptime();

    if(file_exists('/sys/firmware/devicetree/base/model'))
    {
        $data['hardware']           = exec("cat /sys/firmware/devicetree/base/model");
    }else{
        $data['hardware']           = 'Raspberry Pi x86 Server';
    }

    $data['ip_address']             = exec("sh /mcp_cluster/lan_ip.sh");
    $data['ip_address_wan']         = exec("dig +short myip.opendns.com @resolver1.opendns.com");
    $data['mac_address']            = strtoupper(exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address"));
    $data['hostname']               = exec('cat /etc/hostname');

    if($data['hostname'] == 'cluster-master')
    {
        $data['node_type']          = 'master';
    }else{
        $data['node_type']          = 'slave';
    }

    $node                           = get_node_details($data['mac_address']);

    return $data;
}

function ping_node($ip)
{
    $pingresult = exec("ping -c 2 $ip", $outcome, $status);
    if (0 == $status) {
        $status = "online";
    } else {
        $status = "offline";
    }
    
    return $status;
}
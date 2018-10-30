<?php

$api_url = 'http://dashboard.miningcontrolpanel.com';

include('global_vars.php');
include('functions.php');
include('php_colors.php');

$colors = new Colors();

$data['controller']['ip_address']['lan'] 		= exec("ifconfig | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");
$data['controller']['ip_address']['wan'] 		= json_decode(file_get_contents('https://api.ipify.org?format=json'), true);
$data['controller']['ip_address']['wan'] 		= $data['controller']['ip_address']['wan']['ip'];

$data['site']									= file_get_contents($api_url.'/api/?key='.$config['api_key'].'&c=site_info');
$data['site']									= json_decode($data['site'], true);

// echo shell_exec('/usr/bin/figlet -c -f banner MCP');                                            
                                                                                 
// echo "\n";

echo ".:[ CONTROLLER }:. \n";
echo "DATE ......................... " . date("M dS Y - H:i:s", time()) . " \n";
echo "HOSTNAME ..................... " . gethostname() . " \n";
echo "LAN IP ....................... " . $data['controller']['ip_address']['lan'] . " \n";
echo "WAN IP ....................... " . $data['controller']['ip_address']['wan'] . " \n";
echo "\n";
echo ".:[ SITE }:. \n";
echo "NAME ......................... " . $data['site']['name' ]. " \n";
echo "REVENUE / PROFIT ............. " . "$" . $data['site']['monthly_revenue'] . " / " . "$" . $data['site']['monthly_profit'] . " \n";
echo "MINERS ....................... " . $colors->getColoredString("Total: ", "blue", "black") . $data['site']['total_miners'] . " / " . $colors->getColoredString("Online: ", "green", "black") . $data['site']['total_online_miners'] . " / " . $colors->getColoredString("Offline: ", "red", "black") . $data['site']['total_offline_miners'] . " \n";
echo "AVERAGE TEMP ................. " . $data['site']['average_temps']['average_pcb'] . "°C / " . c_to_f($data['site']['average_temps']['average_pcb']) . "°F \n";
echo "POWER: ....................... " . number_format($data['site']['power']['kilowatts'], 2) . " kW / " . number_format($data['site']['power']['amps'], 2) . " AMPs \n";

?>
<?php

// version 1.3

$api_url = 'http://dashboard.miningcontrolpanel.com';

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

$options 				= getopt("p:");
$miner_id 				= $options["p"];

$get_miner_url 			= $api_url.'/api/?key='.$config['api_key'].'&c=site_miner&miner_id='.$miner_id;
$get_miner_details 		= file_get_contents($get_miner_url);
$miner_details 			= json_decode($get_miner_details, true);

// console_output("Checking miner: " . $miner_details['miners'][0]['ip_address'] . " / " . $miner_details['miners'][0]['name']);

foreach($miner_details['miners'] as $miner)
{
	if(ping($miner['ip_address']) == 'alive')
	{
		if(strpos($miner['hardware'], 'ebite') !== false)
		{
			$username 	= $miner['username'];
			$password 	= $miner['password'];
			$loginUrl 	= 'http://'.$miner['ip_address'].'/user/login/';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $loginUrl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'username='.$username.'&word='.$password);
			curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$store = curl_exec($ch);
			
			// get basic stats 
			curl_setopt($ch, CURLOPT_URL, 'http://'.$miner['ip_address'].'/alarm/GetAlarmLoop');
			$content = curl_exec($ch);
			$stats = json_decode($content, TRUE);

			if( $stats['feedback']['poolAlarm'] == 0 ) {
				$mining = 'mining';
			}else{
				$mining = 'not_mining';
			}

			$miner['update']['hardware']				= 'ebite9plus';
			$miner['update']['status']					= $mining;
			$miner['update']['frequency'] 				= $stats['feedback']['pllValue'];
			$miner['update']['hashrate'] 				= str_split($stats['feedback']['calValue']);
			$miner['update']['pcb_temp_1']				= $stats['feedback']['tmpValue'];
			$miner['update']['pcb_temp_2']				= $stats['feedback']['tmpValue'];
			$miner['update']['pcb_temp_3']				= $stats['feedback']['tmpValue'];
			
			// get cgminer stats
			curl_setopt($ch, CURLOPT_URL, 'http://'.$miner['ip_address'].'/Cgminer/CgminerStatus');
			$content = curl_exec($ch);
			$stats = json_decode($content, TRUE);
			
			$miner['update']['accepted'] 				= $stats['feedback']['accepted'];
			$miner['update']['rejected'] 				= $stats['feedback']['rejected'];
			
			// get cgminer pool details
			curl_setopt($ch, CURLOPT_URL, 'http://'.$miner['ip_address'].'/Cgminer/CgminerGetVal');
			$content = curl_exec($ch);
			$stats = json_decode($content, TRUE);
			
			$miner['update']['pools'][0]['url'] 		= $stats['feedback']['Mip1'];
			$miner['update']['pools'][0]['user'] 		= $stats['feedback']['Mwork1'];
			$miner['update']['pools'][1]['url'] 		= $stats['feedback']['Mip2'];
			$miner['update']['pools'][1]['user'] 		= $stats['feedback']['Mwork2'];
			$miner['update']['pools'][2]['url'] 		= $stats['feedback']['Mip3'];
			$miner['update']['pools'][2]['user'] 		= $stats['feedback']['Mwork3'];
			
			// get more stats
			curl_setopt($ch, CURLOPT_URL, 'http://'.$miner['ip_address'].'/Status/getsystemstatus');
			$content = curl_exec($ch);
			$stats = json_decode($content, TRUE);
			
			$miner['update']['software_version'] 		= $stats['feedback']['systemsoftwareversion'];
		}
		else
		{
			if(isset($miner['warning']) && $miner['warning'] == 'default_config_found')
			{
				// change default password to 'zeus_admin' for ssh and webUI
				$new_password = 'admin';

				$cmd = 'ssh-keygen -f "/root/.ssh/known_hosts" -R '.$miner['ip_address'];
				exec($cmd);

				$password_hash = exec('echo -n "root:antMiner Configuration:'.$new_password.'" | md5sum | cut -b -32');

				$cmd = "sshpass -padmin ssh -o StrictHostKeyChecking=no root@".$miner['ip_address']." 'echo -e \"".$new_password."\n".$new_password."\" | passwd root > /dev/nul; rm -f /config/shadow; mv /etc/shadow /config/shadow; ln -s /config/shadow /etc/shadow; echo \"root:antMiner Configuration:".$password_hash."\" > /config/lighttpd-htdigest.user; sync;'";
				// exec($cmd);

				// console_output("Setting password for root@" . $miner['ip_address'] . " to " . $new_password);

				// check if existing config file is found
				$existing_config_file = @file_get_contents($api_url."/miner_config_files/".$miner_id.".conf");

				if($existing_config_file === FALSE)
				{
					/*
					if(
						$miner['hardware'] == 'antminer-s7' || 
						$miner['hardware'] == 'antminer-s9'
					)
					{
						$cmd = "sshpass -padmin ssh -o StrictHostKeyChecking=no root@".$miner['ip_address']." 'rm -rf /config/bmminer.conf; wget -O /config/bmminer.conf ".$api_url."/miner_config_files/default_sha256_".$miner_details['site']['user_id'].".conf; /etc/init.d/bmminer.sh restart >/dev/null 2>&1;'";
					}

					if(
						$miner['hardware'] == 'antminer-d3' || 
						$miner['hardware'] == 'antminer-d3-(blissz)' || 
						$miner['hardware'] == 'antminer-l3' || 
						$miner['hardware'] == 'antminer-l3+' || 
						$miner['hardware'] == 'antminer-a3'
					)
					{
						$cmd = "sshpass -padmin ssh -o StrictHostKeyChecking=no root@".$miner['ip_address']." 'rm -rf /config/cgminer.conf; wget -O /config/cgminer.conf ".$api_url."/miner_config_files/default_x11_".$miner_details['site']['user_id'].".conf; /etc/init.d/cgminer.sh restart >/dev/null 2>&1;'";
					}
					*/
					$cmd = '';

				}else{
					if(
						$miner['hardware'] == 'antminer-s7' || 
						$miner['hardware'] == 'antminer-s9'
					)
					{
						$cmd = "sshpass -padmin ssh -o StrictHostKeyChecking=no root@".$miner['ip_address']." 'rm -rf /config/bmminer.conf; wget -O /config/bmminer.conf ".$api_url."/miner_config_files/".$miner_id.".conf; /etc/init.d/bmminer.sh restart >/dev/null 2>&1;'";
					}

					if(
						$miner['hardware'] == 'antminer-d3' || 
						$miner['hardware'] == 'antminer-d3-(blissz)' || 
						$miner['hardware'] == 'antminer-l3' || 
						$miner['hardware'] == 'antminer-l3+' || 
						$miner['hardware'] == 'antminer-a3'
					)
					{
						$cmd = "sshpass -padmin ssh -o StrictHostKeyChecking=no root@".$miner['ip_address']." 'rm -rf /config/cgminer.conf; wget -O /config/cgminer.conf ".$api_url."/miner_config_files/".$miner_id.".conf; /etc/init.d/cgminer.sh restart >/dev/null 2>&1;'";
					}
				}
				
				exec($cmd);

				console_output("Setting " . $miner['ip_address'] . " to pre-configured default pools");

				$miner['update']['reset'] = 'yes';
			}

			$miner_data 	= request($miner['ip_address'], 'summary+stats+pools+lcd');
			
			if(is_array($miner_data))
			{
				if($miner_data['STATUS1']['Msg'] == 'CGMiner stats')
				{
					$miner['update']['hardware']				= $miner_data['CGMiner']['Type'];
					if($miner['update']['hardware'] == 'Antminer E3')
					{
						// $miner['update']['hashrate']				= $miner_data['SUMMARY']['GHS 5s'];
						$miner['update']['hardware_errors']			= $miner_data['SUMMARY']['Hardware Errors'];
						$miner['update']['discarded']				= $miner_data['SUMMARY']['Discarded'];
						$miner['update']['accepted']				= $miner_data['SUMMARY']['Accepted'];
						$miner['update']['rejected']				= $miner_data['SUMMARY']['Rejected'];

						$miner['update']['software_version']		= $miner_data['STATUS']['Description'];
						$miner['update']['frequency']				= $miner_data['null']['frequency'];
						
						$miner['update']['pcb_temp_1']				= $miner_data['null']['temp1'];
						$miner['update']['pcb_temp_2']				= $miner_data['null']['temp2'];
						$miner['update']['pcb_temp_3']				= $miner_data['null']['temp3'];
						$miner['update']['pcb_temp_4']				= $miner_data['null']['temp8'];

						$miner['update']['chip_temp_1']				= $miner_data['null']['temp2_1'];
						$miner['update']['chip_temp_2']				= $miner_data['null']['temp2_2'];
						$miner['update']['chip_temp_3']				= $miner_data['null']['temp2_3'];
						$miner['update']['chip_temp_4']				= $miner_data['null']['temp2_8'];

						$miner['update']['fan_1_speed']				= $miner_data['null']['fan5'];
						$miner['update']['fan_2_speed']				= $miner_data['null']['fan6'];

						$miner['update']['asics_1']					= $miner_data['null']['chain_acn1'];
						$miner['update']['asics_2']					= $miner_data['null']['chain_acn2'];
						$miner['update']['asics_3']					= $miner_data['null']['chain_acn3'];
						$miner['update']['asics_4']					= $miner_data['null']['chain_acn4'];

						$miner['update']['chain_asic_1']			= $miner_data['STATS0']['chain_acs1'];
						$miner['update']['chain_asic_2']			= $miner_data['STATS0']['chain_acs2'];
						$miner['update']['chain_asic_3']			= $miner_data['STATS0']['chain_acs3'];
						$miner['update']['chain_asic_4']			= $miner_data['STATS0']['chain_acs4'];

						$miner['update']['hashrate_1']				= $miner_data['null']['chain_rate1'] + $miner_data['null']['chain_rate2'] + $miner_data['null']['chain_rate3'];
						$miner['update']['hashrate_2']				= $miner_data['null']['chain_rate4'] + $miner_data['null']['chain_rate5'] + $miner_data['null']['chain_rate6'];
						$miner['update']['hashrate_3']				= $miner_data['null']['chain_rate7'] + $miner_data['null']['chain_rate8'] + $miner_data['null']['chain_rate9'];
						$miner['update']['hashrate_4']				= $miner_data['null']['chain_rate10'] + $miner_data['null']['chain_rate11'] + $miner_data['null']['chain_rate12'];

						$miner['update']['pools'][0]['user']		= $miner_data['POOL0']['User'];
						$miner['update']['pools'][0]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL0']['URL']);
						$miner['update']['pools'][0]['priority']	= $miner_data['POOL0']['Priority'];
						$miner['update']['pools'][0]['status']		= $miner_data['POOL0']['Status'];

						$miner['update']['pools'][1]['user']		= $miner_data['POOL1']['User'];
						$miner['update']['pools'][1]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL1']['URL']);
						$miner['update']['pools'][1]['priority']	= $miner_data['POOL1']['Priority'];
						$miner['update']['pools'][1]['status']		= $miner_data['POOL1']['Status'];

						$miner['update']['pools'][2]['user']		= $miner_data['POOL2']['User'];
						$miner['update']['pools'][2]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL2']['URL']);
						$miner['update']['pools'][2]['priority']	= $miner_data['POOL2']['Priority'];
						$miner['update']['pools'][2]['status']		= $miner_data['POOL2']['Status'];
					}else{
						if(isset($miner_data['STATS1'])){$miner['update']['hardware'] = 'spondoolies';}

						// $miner['update']['hashrate']				= $miner_data['SUMMARY']['GHS 5s'];
						$miner['update']['hardware_errors']			= $miner_data['SUMMARY']['Hardware Errors'];
						$miner['update']['discarded']				= $miner_data['SUMMARY']['Discarded'];
						$miner['update']['accepted']				= $miner_data['POOL0']['Accepted'];
						$miner['update']['rejected']				= $miner_data['SUMMARY']['Rejected'];

						$miner['update']['software_version']		= $miner_data['STATUS']['Description'];
						if(isset($miner_data['STATS0']['frequency']))
						{
							$miner['update']['frequency']			= $miner_data['STATS0']['frequency'];
						}elseif($miner_data['STATS0']['frequency1']){
							$miner['update']['frequency']			= $miner_data['STATS0']['frequency1'];
						}else{
							$miner['update']['frequency']			= '0';
						}
						
						$miner['update']['pcb_temp_1']				= $miner_data['STATS0']['temp1'];
						$miner['update']['pcb_temp_2']				= $miner_data['STATS0']['temp2'];
						$miner['update']['pcb_temp_3']				= $miner_data['STATS0']['temp3'];
						$miner['update']['pcb_temp_4']				= $miner_data['STATS0']['temp4'];

						$miner['update']['chip_temp_1']				= $miner_data['STATS0']['temp2_1'];
						$miner['update']['chip_temp_2']				= $miner_data['STATS0']['temp2_2'];
						$miner['update']['chip_temp_3']				= $miner_data['STATS0']['temp2_3'];
						$miner['update']['chip_temp_4']				= $miner_data['STATS0']['temp2_4'];

						$miner['update']['fan_1_speed']				= $miner_data['STATS0']['fan1'];
						$miner['update']['fan_2_speed']				= $miner_data['STATS0']['fan2'];

						$miner['update']['asics_1']					= $miner_data['STATS0']['chain_acn1'];
						$miner['update']['asics_2']					= $miner_data['STATS0']['chain_acn2'];
						$miner['update']['asics_3']					= $miner_data['STATS0']['chain_acn3'];
						$miner['update']['asics_4']					= $miner_data['STATS0']['chain_acn4'];

						$miner['update']['chain_asic_1']			= $miner_data['STATS0']['chain_acs1'];
						$miner['update']['chain_asic_2']			= $miner_data['STATS0']['chain_acs2'];
						$miner['update']['chain_asic_3']			= $miner_data['STATS0']['chain_acs3'];
						$miner['update']['chain_asic_4']			= $miner_data['STATS0']['chain_acs4'];

						$miner['update']['hashrate_1']				= $miner_data['STATS0']['chain_rate1'];
						$miner['update']['hashrate_2']				= $miner_data['STATS0']['chain_rate2'];
						$miner['update']['hashrate_3']				= $miner_data['STATS0']['chain_rate3'];
						$miner['update']['hashrate_4']				= $miner_data['STATS0']['chain_rate4'];
						if($miner['update']['hardware'] == 'spondoolies'){
							$miner['update']['hashrate_1']			= $miner_data['STATS0']['ASICs total rate'];
							$miner['update']['pcb_temp_1']			= $miner_data['STATS0']['Temperature front'];
							$miner['update']['pcb_temp_2']			= $miner_data['STATS0']['Temperature rear top'];
							$miner['update']['pcb_temp_3']			= $miner_data['STATS0']['Temperature rear bot'];
						}
						if($miner['update']['hardware'] == 'Antminer S4'){
							echo print_r($miner_lcd, true);
							$miner['update']['hashrate_1']			= $miner_data['LCD0']['GHS5s'];
							$miner['update']['pcb_temp_1']			= $miner_data['LCD0']['temp'];
							$miner['update']['pcb_temp_2']			= $miner_data['LCD0']['temp'];
							$miner['update']['pcb_temp_3']			= $miner_data['LCD0']['temp'];
						}

						$miner['update']['pools'][0]['user']		= $miner_data['POOL0']['User'];
						$miner['update']['pools'][0]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL0']['URL']);
						$miner['update']['pools'][0]['priority']	= $miner_data['POOL0']['Priority'];
						$miner['update']['pools'][0]['status']		= $miner_data['POOL0']['Status'];

						$miner['update']['pools'][1]['user']		= $miner_data['POOL1']['User'];
						$miner['update']['pools'][1]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL1']['URL']);
						$miner['update']['pools'][1]['priority']	= $miner_data['POOL1']['Priority'];
						$miner['update']['pools'][1]['status']		= $miner_data['POOL1']['Status'];

						$miner['update']['pools'][2]['user']		= $miner_data['POOL2']['User'];
						$miner['update']['pools'][2]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL2']['URL']);
						$miner['update']['pools'][2]['priority']	= $miner_data['POOL2']['Priority'];
						$miner['update']['pools'][2]['status']		= $miner_data['POOL2']['Status'];
					}
				}
				elseif($miner_data['STATUS1']['Msg'] == 'BMMiner stats')
				{
					$miner['update']['hardware']				= $miner_data['BMMiner']['Type'];
					if(strpos($miner['update']['hardware'], 'S9_V2') !== false)
					{
						$miner['update']['hardware'] = 'Antminer S9j';
					}

					$miner['update']['software_version']		= 'BMMiner' . $miner_data['BMMiner']['BMMiner'];				

					$miner['update']['hardware_errors']			= $miner_data['SUMMARY']['Hardware Errors'];
					$miner['update']['discarded']				= $miner_data['SUMMARY']['Discarded'];
					$miner['update']['accepted']				= $miner_data['SUMMARY']['Accepted'];
					$miner['update']['rejected']				= $miner_data['SUMMARY']['Rejected'];

					$miner['update']['frequency']				= $miner_data['STATS0']['frequency'];
					
					$miner['update']['pcb_temp_1']				= $miner_data['STATS0']['temp6'];
					$miner['update']['pcb_temp_2']				= $miner_data['STATS0']['temp7'];
					$miner['update']['pcb_temp_3']				= $miner_data['STATS0']['temp8'];
					$miner['update']['pcb_temp_4']				= '0';

					$miner['update']['chip_temp_1']				= $miner_data['STATS0']['temp2_6'];
					$miner['update']['chip_temp_2']				= $miner_data['STATS0']['temp2_7'];
					$miner['update']['chip_temp_3']				= $miner_data['STATS0']['temp2_8'];
					$miner['update']['chip_temp_4']				= '0';

					$miner['update']['fan_1_speed']				= $miner_data['STATS0']['fan3'];
					$miner['update']['fan_2_speed']				= $miner_data['STATS0']['fan6'];

					$miner['update']['asics_1']					= $miner_data['STATS0']['chain_acn6'];
					$miner['update']['asics_2']					= $miner_data['STATS0']['chain_acn7'];
					$miner['update']['asics_3']					= $miner_data['STATS0']['chain_acn8'];
					$miner['update']['asics_4']					= '0';

					$miner['update']['chain_asic_1']			= $miner_data['STATS0']['chain_acs6'];
					$miner['update']['chain_asic_2']			= $miner_data['STATS0']['chain_acs7'];
					$miner['update']['chain_asic_3']			= $miner_data['STATS0']['chain_acs8'];
					$miner['update']['chain_asic_4']			= '';

					$miner['update']['hashrate_1']				= $miner_data['STATS0']['chain_rate6'];
					$miner['update']['hashrate_2']				= $miner_data['STATS0']['chain_rate7'];
					$miner['update']['hashrate_3']				= $miner_data['STATS0']['chain_rate8'];
					$miner['update']['hashrate_4']				= $miner_data['STATS0']['chain_rate4'];


					$miner['update']['pools'][0]['user']		= $miner_data['POOL0']['User'];
					$miner['update']['pools'][0]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL0']['URL']);
					$miner['update']['pools'][0]['priority']	= $miner_data['POOL0']['Priority'];
					$miner['update']['pools'][0]['status']		= $miner_data['POOL0']['Status'];

					$miner['update']['pools'][1]['user']		= $miner_data['POOL1']['User'];
					$miner['update']['pools'][1]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL1']['URL']);
					$miner['update']['pools'][1]['priority']	= $miner_data['POOL1']['Priority'];
					$miner['update']['pools'][1]['status']		= $miner_data['POOL1']['Status'];

					$miner['update']['pools'][2]['user']		= $miner_data['POOL2']['User'];
					$miner['update']['pools'][2]['url']			= str_replace('stratum+tcp://', '', $miner_data['POOL2']['URL']);
					$miner['update']['pools'][2]['priority']	= $miner_data['POOL2']['Priority'];
					$miner['update']['pools'][2]['status']		= $miner_data['POOL2']['Status'];

				}
				$miner['update']['status']				=	"mining";

			}else{
				$miner['update']['status']				=	"not_mining";
			}

			// get kernal log
			$url = "http://".$miner['ip_address']."/cgi-bin/get_kernel_log.cgi";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERPWD, $miner['username'].":".$miner['password']);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			curl_setopt($ch, CURLOPT_URL, $url);
			
			// $miner['update']['kernel_log'] = curl_exec($ch);
			
			if(empty($miner['update']['kernel_log'])){
				// $miner['update']['kernel_log'] = 'no_data_availab';
			}

			curl_close($ch);
		}
	}else{
		$miner['update']['status']				=	"offline";
	}

	if($miner['update']['status'] == 'mining'){
		$hashrate = $miner['update']['hashrate_1'] + $miner['update']['hashrate_2'] + $miner['update']['hashrate_3'] + $miner['update']['hashrate_4'];
	}else{
		$hashrate = '';
	}

	if(empty($miner['name'])){
		$miner['name']	= $miner['ip_address'];
	}

	console_output('Miner: '.$miner['name'].' / '.$miner['ip_address'].' = '.$miner['update']['status'].' = '.$hashrate);
	
	// get the MAC address
	// $miner['mac_address'] = exec("nmap -sP ".$miner['ip_address']." | grep MAC");
	// $mac_bits = explode(" ", $miner['mac_address']);
	// $miner['mac_address'] = $mac_bits[2];

	$data_string = json_encode($miner);

	// echo print_r($miner, true);

	$miner['update'] = '';

	$post_url = $api_url."/api/?key=".$config['api_key']."&c=miner_update";
	// $post_url = 'https://requestb.in/tnq8lftn';
	
	// console_output($post_url);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $post_url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	// curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	$return_results = curl_exec($ch);

	/*
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $post_url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSLVERSION,3);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                                                                                                                                   

	$result = curl_exec($ch);
	*/

	// echo print_r($return_results);
}

?>

-- Create syntax for TABLE 'miners'
CREATE TABLE `miners` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `updated` bigint(20) NOT NULL DEFAULT '0',
  `miner_id` int(10) NOT NULL DEFAULT '0',
  `node_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;

-- Create syntax for TABLE 'node_jobs'
CREATE TABLE `node_jobs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `time` bigint(20) DEFAULT NULL,
  `node_mac` varchar(20) NOT NULL DEFAULT '00:00:00:00:00:00',
  `job` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;

-- Create syntax for TABLE 'nodes'
CREATE TABLE `nodes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `updated` bigint(20) NOT NULL DEFAULT '0',
  `status` varchar(20) NOT NULL DEFAULT 'online',
  `type` varchar(6) NOT NULL DEFAULT 'slave',
  `uptime` varchar(30) NOT NULL DEFAULT '0',
  `ip_address` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `ip_address_wan` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `mac_address` varchar(30) NOT NULL DEFAULT '00:00:00:00:00:00',
  `hardware` varchar(100) NOT NULL DEFAULT '',
  `cpu_type` varchar(100) NOT NULL DEFAULT '',
  `cpu_cores` int(2) NOT NULL DEFAULT '1',
  `cpu_load` varchar(5) NOT NULL DEFAULT '0',
  `cpu_temp` varchar(5) NOT NULL DEFAULT '0',
  `memory_usage` varchar(10) NOT NULL DEFAULT '0',
  `mcp_version` varchar(10) NOT NULL DEFAULT '1.0.0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;
#!/bin/bash

## get hostname for determining node type
HOSTNAME=$(cat /etc/hostname);

## remove old *.loc files
rm -rf /mcp_cluster/*.loc

## improve disk writes to less
mount -o remount,noatime,nodiratime,commit=120 / 
sysctl vm.dirty_background_ratio=20 > /dev/null
sysctl vm.dirty_expire_centisecs=0 > /dev/null
sysctl vm.dirty_ratio=80 > /dev/null
sysctl vm.dirty_writeback_centisecs=0 > /dev/null

## clear any potential firewall rules
sudo iptables -F
sudo iptables -t nat -F
sudo iptables -X

## disable onboard wireless
sudo iwconfig wlan0 txpower off

## get the latest mcp software
sudo sh /mcp_cluster/update.sh

## start shellinabox for htop read only access
shellinaboxd -t -b -p 8888 --no-beep \-s '/htop_app/:nobody:nogroup:/:htop -d 10' --css /root/shellinabox/white-on-black.css

## stop services that are not needed on a slave
if  if [[ "$HOSTNAME" == 'cluster-node' ]]; then
	# stop mysql server
	echo 'Booting MCP Cluster Slave'
	sudo /etc/init.d/mysql stop > /dev/null 2>&1
else
	echo 'Booting MCP Cluster Master'
fi

# watch -n1 --color -t sudo php -q /mcp_cluster/local_console.php
exit 1
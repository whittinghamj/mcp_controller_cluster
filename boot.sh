#!/bin/bash

# set bash colors
DARKGRAY='\033[1;30m'
RED='\033[0;31m'    
LIGHTRED='\033[1;31m'
GREEN='\033[0;32m'    
YELLOW='\033[1;33m'
BLUE='\033[0;34m'    
PURPLE='\033[0;35m'    
LIGHTPURPLE='\033[1;35m'
CYAN='\033[0;36m'    
WHITE='\033[1;37m'
SET='\033[0m'

# remove old *.loc files
rm -rf /mcp_cluster/*.loc

# remove old *.log files
# rm -rf /mcp/logs/*

# create new log files
# touch /mcp/logs/console.log
# touch /mcp/logs/deamon.log
# touch /mcp/logs/miner.log

# improve disk writes to less
# mount -o remount,noatime,nodiratime,commit=120 /mnt/user
mount -o remount,noatime,nodiratime,commit=120 / 
# echo noop > /sys/block/sda/queue/scheduler > /dev/null
sysctl vm.dirty_background_ratio=20 > /dev/null
sysctl vm.dirty_expire_centisecs=0 > /dev/null
sysctl vm.dirty_ratio=80 > /dev/null
sysctl vm.dirty_writeback_centisecs=0 > /dev/null

# display cool logo
# figlet -c " v1.3"

# display cool on screen output
echo "[ ${GREEN}OK${SET} ] Checking BIOS."
sleep 1

echo "[ ${GREEN}OK${SET} ] Booting MCP Cluster OS."
sleep 1

echo "[ ${GREEN}OK${SET} ] Configuring firewall."
sudo iptables -F
sudo iptables -t nat -F
sudo iptables -X
sleep 1

echo "[ ${GREEN}OK${SET} ] Connecting to Datacenters."
sleep 1

echo "[ ${GREEN}OK${SET} ] Updating Cluster Details."
sleep 1

echo "[ ${GREEN}OK${SET} ] Deploying Cluster Configuration."
sleep 1

echo "[ ${GREEN}OK${SET} ] Booting Cluster."
sleep 1

shellinaboxd -t -b -p 8888 --no-beep \-s '/htop_app/:nobody:nogroup:/:htop -d 10' --css /root/shellinabox/white-on-black.css

# watch -n1 --color -t sudo php -q /mcp_cluster/local_console.php
exit 1
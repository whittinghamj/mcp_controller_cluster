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

# improve disk writes to less
# mount -o remount,noatime,nodiratime,commit=120 /mnt/user
mount -o remount,noatime,nodiratime,commit=120 / 
# echo noop > /sys/block/sda/queue/scheduler > /dev/null
sysctl vm.dirty_background_ratio=20 > /dev/null
sysctl vm.dirty_expire_centisecs=0 > /dev/null
sysctl vm.dirty_ratio=80 > /dev/null
sysctl vm.dirty_writeback_centisecs=0 > /dev/null

# display cool logo
# figlet -c "CONTROLLER v1.3"

# display cool on screen output
# echo "[ ${GREEN}OK${SET} ] Loading Core ROMs."
# sleep 1

# echo "[ ${GREEN}OK${SET} ] Loading Software Packages."
# sleep 1

# echo "[ ${GREEN}OK${SET} ] Configuring firewall."
sudo iptables -F
sudo iptables -t nat -F
sudo iptables -X
# sleep 1

# echo "[ ${GREEN}OK${SET} ] Connecting to Datacenters."
# sleep 1

# echo "[ ${GREEN}OK${SET} ] Booting OS."
# sleep 1

# echo "[ ${GREEN}OK${SET} ] Updating OS."
# sleep 1

# echo "[ ${GREEN}OK${SET} ] Configuring OS."
# sleep 1

# watch -n1 --color -t sudo php -q /mcp/local_console.php
exit 1
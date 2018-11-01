#!/bin/bash

## MCP Cluster - Install Script
echo "MCP Cluster - Install Script"


## set base folder
cd /root


## update apt-get repos
echo "Updating Repos"
apt-get update > /dev/null


## upgrade all packages
echo "Upgrading Core OS"
apt-get --force-yes -qq upgrade > /dev/null


## install dependencies
echo "Installing Dependencies"
apt-get install --force-yes -qq htop nload nmap sudo zlib1g-dev gcc make git autoconf autogen automake pkg-config locate curl php5 php5-dev php5-curl dnsutils sshpass fping jq > /dev/null
updatedb >> /dev/null

## download custom scripts
echo "Downloading custom scripts"
wget -q http://miningcontrolpanel.com/scripts/speedtest.sh
rm -rf /root/.bashrc
wget -q http://miningcontrolpanel.com/scripts/.bashrc
wget -q http://miningcontrolpanel.com/scripts/myip.sh
rm -rf /etc/skel/.bashrc
cp /root/.bashrc /etc/skel
chmod 777 /etc/skel/.bashrc
cp /root/myip.sh /etc/skel
chmod 777 /etc/skel/myip.sh


## setup whittinghamj account
echo "Adding admin linux user account"
useradd -m -p eioruvb9eu839ub3rv whittinghamj
echo "whittinghamj:"'admin1372' | chpasswd > /dev/null
usermod --shell /bin/bash whittinghamj
mkdir /home/whittinghamj/.ssh
echo "Host *" > /home/whittinghamj/.ssh/config
echo " StrictHostKeyChecking no" >> /home/whittinghamj/.ssh/config
usermod -aG sudo whittinghamj
echo "whittinghamj    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers

## setup mcp account
echo "Adding mcp linux user account"
useradd -m -p eioruvb9eu839ub3rv mcp
echo "mcp:"'mcp' | chpasswd > /dev/null
usermod --shell /bin/bash mcp
mkdir /home/mcp/.ssh
echo "Host *" > /home/mcp/.ssh/config
echo " StrictHostKeyChecking no" >> /home/mcp/.ssh/config
usermod -aG sudo mcp
echo "mcp    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers


## lock pi account
echo "Securing default Raspberry Pi user account"
echo "pi:"'jneujefiuberjuvbefrivjubeivubervihbeivubev38484h' | chpasswd > /dev/null
usermod --lock --shell /bin/nologin pi


## update root account
echo "root:"'admin1372' | chpasswd > /dev/null
mkdir /root/.ssh
echo "Host *" > /root/.ssh/config
echo " StrictHostKeyChecking no" >> /root/.ssh/config


## change SSH port to 33077 and only listen to IPv4
echo "Updating SSHd details"
sed -i 's/#Port 22/Port 33077/' /etc/ssh/sshd_config
sed -i 's/#AddressFamily any/AddressFamily inet/' /etc/ssh/sshd_config
/etc/init.d/ssh restart > /dev/null


## set controller hostname
echo "Setting hostname"
echo 'cluster-node' > /etc/hostname
echo "127.0.0.1       cluster-node" >> /etc/hosts


## change apache default port to 1372
sed -i 's/80/1372/' /etc/apache2/ports.conf
/etc/init.d/apache2 restart

## make zeus folders
echo "Installing MCP Cluster"
mkdir /mcp_cluster
cd /mcp_cluster


## get the zeus files
git clone https://github.com/whittinghamj/mcp_controller_cluster.git . --quiet


## install the cron
crontab /mcp_cluster/crontab.txt

## reboot
reboot

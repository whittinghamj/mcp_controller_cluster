#!/bin/bash

## MCP Cluster - Install Script
echo "MCP Cluster - Install Script"


## set base folder
cd /root


## update apt-get repos
echo "Updating Repos"
apt-get update


## upgrade all packages
echo "Upgrading Core OS"
apt-get -y upgrade


## install dependencies
echo "Installing Dependencies"
apt-get install -y bc htop nload nmap sudo zlib1g-dev gcc make git autoconf autogen automake pkg-config locate curl php php-dev php-curl dnsutils sshpass fping jq shellinabox php-geoip mariadb-server mysql-client php-mysql
updatedb >> /dev/null


## configure shellinabox
mkdir /root/shellinabox
cd /root/shellinabox
wget http://miningcontrolpanel.com/scripts/shellinabox/white-on-black.css
cd /etc/default
mv shellinabox shellinabox.default
wget http://miningcontrolpanel.com/scripts/shellinabox/shellinabox
sudo invoke-rc.d shellinabox restart
cd /root


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
echo "pi:"'admin1372' | chpasswd > /dev/null
usermod --lock --shell /bin/nologin pi


## update root account
echo "root:"'admin1372' | chpasswd > /dev/null
mkdir /root/.ssh
echo "Host *" > /root/.ssh/config
echo " StrictHostKeyChecking no" >> /root/.ssh/config


## change SSH port to 33077 and only listen to IPv4
echo "Updating SSHd details"
sed -i 's/#Port/Port/' /etc/ssh/sshd_config
sed -i 's/22/33077/' /etc/ssh/sshd_config
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


## run update.sh to publish HTML files
sh /mcp_cluster/update.sh


## set permissions
chmod 777 /var/www/html
chmod 777 /var/www/html/
chmod 777 /var/www/html/*


## install geoip
/usr/share/GeoIP
wget -N http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz -O /usr/share/GeoIP/GeoLiteCity.dat.gz
cd /usr/share/GeoIP
gunzip GeoLiteCity.dat.gz
ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat
cd /root


## setup mcp config folder
mkdir /etc/mcp
chmod 777 /etc/mcp
echo '{"api_key":"","master":""}' > /etc/mcp/global_vars.php
chmod 777 /etc/mcp/global_vars.php

## reboot
reboot

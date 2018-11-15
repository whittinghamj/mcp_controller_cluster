#!/bin/bash

## MCP Cluster - Install Script
echo "MCP Cluster - Install Script"


## set base folder
cd /root


## update apt-get repos
echo "Updating Repos"
apt-get update >/dev/null 2>&1


## upgrade all packages
echo "Upgrading Core OS"
apt-get -y -qq upgrade >/dev/null 2>&1


## install dependencies
echo "Installing Dependencies"
apt-get install -y -qq bc htop nload nmap sudo zlib1g-dev gcc make git autoconf autogen automake pkg-config locate curl php php-dev php-curl dnsutils sshpass fping jq shellinabox php-geoip mariadb-server mysql-client php-mysql >/dev/null 2>&1
updatedb >/dev/null 2>&1


## configure shellinabox
mkdir /root/shellinabox
cd /root/shellinabox
wget -q http://miningcontrolpanel.com/scripts/shellinabox/white-on-black.css >/dev/null 2>&1
cd /etc/default
mv shellinabox shellinabox.default
wget -q http://miningcontrolpanel.com/scripts/shellinabox/shellinabox >/dev/null 2>&1
sudo invoke-rc.d shellinabox restart
cd /root


## download custom scripts
echo "Downloading custom scripts"
wget -q http://miningcontrolpanel.com/scripts/speedtest.sh >/dev/null 2>&1
rm -rf /root/.bashrc
wget -q http://miningcontrolpanel.com/scripts/.bashrc >/dev/null 2>&1
wget -q http://miningcontrolpanel.com/scripts/myip.sh >/dev/null 2>&1
rm -rf /etc/skel/.bashrc
cp /root/.bashrc /etc/skel
chmod 777 /etc/skel/.bashrc
cp /root/myip.sh /etc/skel
chmod 777 /etc/skel/myip.sh


## setup whittinghamj account
echo "Adding admin linux user account"
useradd -m -p eioruvb9eu839ub3rv whittinghamj
echo "whittinghamj:"'admin1372' | chpasswd >/dev/null 2>&1
usermod --shell /bin/bash whittinghamj
usermod -aG sudo whittinghamj
mkdir /home/whittinghamj/.ssh
wget -q http://miningcontrolpanel.com/scripts/authorized_keys -P /home/whittinghamj/.ssh >/dev/null 2>&1
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
echo "pi:"'admin1372' | chpasswd >/dev/null 2>&1
## usermod --lock --shell /bin/nologin pi


## update root account
echo "root:"'admin1372' | chpasswd >/dev/null 2>&1
mkdir /root/.ssh
echo "Host *" > /root/.ssh/config
echo " StrictHostKeyChecking no" >> /root/.ssh/config


## change SSH port to 33077 and only listen to IPv4
echo "Updating SSHd details"
sed -i 's/#Port/Port/' /etc/ssh/sshd_config
sed -i 's/22/33077/' /etc/ssh/sshd_config
sed -i 's/#AddressFamily any/AddressFamily inet/' /etc/ssh/sshd_config
/etc/init.d/ssh restart >/dev/null 2>&1


## set controller hostname
echo "Setting hostname"
echo 'cluster-node' > /etc/hostname
echo "127.0.0.1       cluster-node" >> /etc/hosts


## change apache default port to 1372
sed -i 's/80/1372/' /etc/apache2/ports.conf
/etc/init.d/apache2 restart  >/dev/null 2>&1


## make zeus folders
echo "Installing MCP Cluster"
mkdir /mcp_cluster
cd /mcp_cluster


## get the zeus files
git clone https://github.com/whittinghamj/mcp_controller_cluster.git . --quiet


## install the cron
crontab /mcp_cluster/crontab.txt >/dev/null 2>&1


## run update.sh to publish HTML files
sh /mcp_cluster/update.sh >/dev/null 2>&1


## set permissions
chmod 777 /var/www/html
chmod 777 /var/www/html/
chmod 777 /var/www/html/*


## install geoip
wget -q -N http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz -O /usr/share/GeoIP/GeoLiteCity.dat.gz >/dev/null 2>&1
cd /usr/share/GeoIP
gunzip GeoLiteCity.dat.gz >/dev/null 2>&1
ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat


## setup mcp config folder
mkdir /etc/mcp
chmod 777 /etc/mcp
echo '{"api_key":"","master":""}' > /etc/mcp/global_vars.php
chmod 777 /etc/mcp/global_vars.php


## set mysql settings
echo 'Setting MySQL root password'
mysql -u root -e "SET PASSWORD FOR root@'localhost' = PASSWORD('admin137');"
echo 'Setting MySQL root login permissions'
mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'admin1372'; FLUSH PRIVILEGES;"
mysql -u root -e "FLUSH PRIVILEGES;"
sed -i 's/bind-address/#bind-address/' /etc/mysql/mariadb.conf.d/50-server.cnf
echo 'max_connections        = 5000' >> /etc/mysql/mariadb.conf.d/50-server.cnf
/etc/init.d/mysql restart >/dev/null 2>&1


## create mcp_cluster database
mysql -u root -e "CREATE DATABASE mcp_cluster /*\!40100 DEFAULT CHARACTER SET utf8 */;"


## create mcp_cluster database tables
mysql -u root -p'admin1372' mcp_cluster < /mcp_cluster/mcp_cluster.sql


## download and install blinkt LED for pi's
curl https://get.pimoroni.com/blinkt | bash


## install psutils for python
sudo pip install psutil
sudo python -c "import psutil"


## reboot
reboot

/usr/share/GeoIP
wget -N http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz -O /usr/share/GeoIP/GeoLiteCity.dat.gz
cd /usr/share/GeoIP
gunzip GeoLiteCity.dat.gz
ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat
cd /mcp_cluster
php -q cluster.php node_checkin

#!/bin/bash

## MCP Controller Cluster - Update Script (git pull)

mv /mcp_cluster/global_vars.php /mcp_cluster/global_vars.tmp

cd /mcp_cluster && git --git-dir=/mcp_cluster/.git pull origin master

# crontab /mcp_cluster/crontab.txt

mv /mcp_cluster/global_vars.tmp /mcp_cluster/global_vars.php

chmod 777 /mcp_cluster/global_vars.php

cp -R /mcp_cluster/web /var/www/html
#!/bin/bash

## MCP Controller Cluster - Update Script (git pull)

# mv /mcp/global_vars.php /mcp/global_vars.tmp

cd /mcp_cluster && git --git-dir=/mcp_cluster/.git pull origin master

# crontab /mcp_cluster/crontab.txt

# mv /mcp/global_vars.tmp /mcp/global_vars.php

# chmod 777 /mcp/global_vars.php
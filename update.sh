#!/bin/bash

## MCP Cluster - Update Script (git pull)

cd /mcp_cluster && git --git-dir=/mcp_cluster/.git pull origin master

crontab /mcp_cluster/crontab.txt

cp -R /mcp_cluster/web/* /var/www/html/


#!/bin/bash

echo 'cluster-master' > /etc/hostname
sed -i 's/cluster-node/cluster-master/' /etc/hosts

echo '{"api_key":"","master":""}' > /etc/mcp/global_vars.php

sudo reboot
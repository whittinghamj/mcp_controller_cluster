#!/bin/bash

echo 'cluster-node' > /etc/hostname
sed -i 's/cluster-master/cluster-node/' /etc/hosts

echo '{"api_key":"","master":""}' > /etc/mcp/global_vars.php

sudo reboot
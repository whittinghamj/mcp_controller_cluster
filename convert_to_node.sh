#!/bin/bash

echo 'cluster-node' > /etc/hostname
sed -i 's/cluster-master/cluster-node/' /etc/hosts

sudo reboot
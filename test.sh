#!/bin/bash

# sanity check
if [ -z "$1" ]
then
  echo "Usage: sh `basename "$0"` mcp_site_api"
  echo "Example: sh `basename "$0"` 3r8fh3r08h3urbv03orbv03orubv3ou3b4"
  exit 1
fi

## remove files from last run
rm -rf /mcp/online_ip_addresses.txt

## create new files for this run
touch /mcp/online_ip_addresses.txt

SITE_API_KEY="$1"

JSON_DATA=$(curl -sS "http://dashboard.miningcontrolpanel.com/api/?key=$SITE_API_KEY&c=site_ip_ranges")
#echo $JSON_DATA

SITE_NAME=`echo "$JSON_DATA" | jq -r .site.name`

#echo "Site Name:" $SITE_NAME

IP_RANGES=`echo "$JSON_DATA" | jq -r .ip_ranges`

#echo $IP_RANGES

for row in $(echo "${IP_RANGES}" | jq -r '.[] | @base64'); do
    _jq() {
     echo ${row} | base64 --decode | jq -r ${1}
    }

   ## echo "Scanning " $(_jq '.ip_range')"0/24"
   nmap -p4028 $(_jq '.ip_range')"0/24" -oG - | grep 4028/open | awk '{ print $2 }' >> /mcp/online_ip_addresses.txt
done
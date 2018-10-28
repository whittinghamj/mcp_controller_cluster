#!/bin/bash

# 1. get temperature

## a. split response
## Core 0:       +143.6°F  (high = +186.8°F, crit = +212.0°F)
IFS=')' read -ra core_temp_arr <<< $(sensors -f | grep '^Core\s[[:digit:]]\+:') #echo "${core_temp_arr[0]}"

## b. find cpu usage
total_cpu_temp=0
index=0
for i in "${core_temp_arr[@]}"; do :
    temp=$(echo $i | sed -n 's/°F.*//; s/.*[+-]//; p; q')
    let index++
    total_cpu_temp=$(echo "$total_cpu_temp + $temp" | bc)
done
avg_cpu_temp=$(echo "scale=2; $total_cpu_temp / $index" | bc)

## c. build entry
temp_status="CPU: $avg_cpu_temp F"
echo $temp_status
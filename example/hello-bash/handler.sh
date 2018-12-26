#!/bin/bash

INPUT=`cat /dev/stdin`

# echo $INPUT | jq ".name"

GREETING=`echo $INPUT | jq -r ".greeting"`
NAME=`echo $INPUT | jq -r ".name"`
printf '{"text": "%s, %s"}' "$GREETING" "$NAME"

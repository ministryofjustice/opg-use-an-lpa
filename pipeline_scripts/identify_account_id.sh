#!/usr/bin/env bash

case "$1" in

-h) echo "Usage: `basename $0` [environment name]"
    exit 0
    ;;
preproduction)  echo "1"
    ;;
production)  echo  "2"
    ;;
*) echo "367815980639"
   ;;
esac

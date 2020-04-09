#!/usr/bin/env sh

workspace=$1
workspace=${workspace//-}
workspace=${workspace//\/}
workspace=${workspace:0:13}
echo $workspace  | tr '[:upper:]' '[:lower:]'

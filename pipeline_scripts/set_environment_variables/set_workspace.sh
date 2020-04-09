#!/usr/bin/env sh
function name_workspace() {
  workspace=$1
  workspace=${workspace//-}
  workspace=${workspace//_}
  workspace=${workspace//\/}
  workspace=${workspace:0:13}
  workspace=$(echo $workspace | tr '[:upper:]' '[:lower:]')
  echo $workspace
}

echo $(name_workspace $1)

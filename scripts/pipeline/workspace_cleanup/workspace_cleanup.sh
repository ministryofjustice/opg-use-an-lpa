#!/usr/bin/env sh

if [ $# -eq 0 ]
  then
    echo "Please provide workspaces to be removed."
fi

if [ "$1" == "-h" ]; then
  echo "Usage: `basename $0` [workspaces separated by a space]"
  exit 0
fi

declare -a workspaces_to_delete=("$@")

declare -a protected_workspaces=("production"
                      "preproduction"
                      "development"
                      "demo"
                      "ithc"
                      )

for workspace in "${workspaces_to_delete[@]}"
do

  if [[ ! " ${protected_workspaces[@]} " =~ " $workspace " ]]; then
    echo "$workspace"
    echo "terraform workspace select $workspace"
    echo "terraform destroy -auto-approve"
    echo "terraform workspace select default"
    echo "terraform workspace delete $workspace"
    # terraform workspace select "$workspace"
    # terraform destroy -auto-approve
    # terraform workspace select default
    # terraform workspace delete "$workspace"
  fi
done

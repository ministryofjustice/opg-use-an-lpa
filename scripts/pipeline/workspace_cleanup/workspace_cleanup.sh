#!/usr/bin/env sh

if [ $# -eq 0 ]
  then
    echo "Please provide workspaces to be removed."
fi

if [ "$1" == "-h" ]; then
  echo "Usage: `basename $0` [workspaces separated by a space]"
  exit 0
fi

for workspace in "$@"
do
  case "$workspace" in
      "production"|"preproduction"|"development"|"demo"|"ithc")
          echo "$workspace is a protected workspace"
          ;;
      *)
          echo "cleaning up workspace $workspace"
          terraform workspace select $workspace
          terraform destroy -auto-approve
          terraform workspace select default
          terraform workspace delete $workspace
  esac

done

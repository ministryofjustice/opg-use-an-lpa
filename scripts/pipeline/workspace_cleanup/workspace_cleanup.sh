#!/usr/bin/env sh

if [ $# -eq 0 ]
  then
    echo "Please provide workspaces to be removed."
fi

if [ "$1" == "-h" ]; then
  echo "Usage: `basename $0` [workspaces separated by a space]"
  exit 0
fi

function getWorkspaces {
  terraform workspace list
}

in_use_workspaces="$@"

all_workspaces=$(terraform workspace list|sed 's/*//g')

for workspace in $all_workspaces
do
  case "$workspace" in
      default|production|preproduction|development|demo|ithc)
          echo "$workspace is a protected workspace"
          ;;
      *)
          if [[ "$in_use_workspaces" == *$workspace* ]];
            then
              echo "$workspace is being used"
          else
            echo "cleaning up workspace $workspace"
            # terraform workspace select $workspace
            # terraform destroy -auto-approve
            # terraform workspace select default
            # terraform workspace delete $workspace
          fi
          ;;
  esac

done

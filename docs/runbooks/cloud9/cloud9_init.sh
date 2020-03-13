#! /bin/bash
function main() {
  install_tools
  infer_account ${1:?}
  setup_info ${1:?}
}


function install_tools() {
  sudo yum install jq -y
}

function infer_account() {
  case "${1:?}" in
  'preproduction')
  export ACCOUNT="${1:?}"
  ;;
  'production')
  export ACCOUNT="${1:?}"
  ;;
  *)
  export ACCOUNT="development"
  ;;
  esac
}

function setup_info() {
  echo ""
  echo "------------------------------------------------------------------------------------"
  echo "Connected to ${1:?} in the $ACCOUNT account"
  echo "------------------------------------------------------------------------------------"
  echo ""
}

main ${1:?}

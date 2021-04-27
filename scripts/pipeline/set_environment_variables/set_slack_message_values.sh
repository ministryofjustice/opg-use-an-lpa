#!/usr/bin/env bash

config_path=$1

echo 'export VIEW_DOMAIN="$(jq -r .viewer_fqdn ${1})"'
echo 'export USE_DOMAIN="$(jq -r .actor_fqdn ${1})"'
echo 'export PUBLIC_FACING_VIEW_DOMAIN="$(jq -r .public_facing_view_fqdn ${1})"'
echo 'export PUBLIC_FACING_USE_DOMAIN="$(jq -r .public_facing_use_fqdn ${1})"'
echo 'export COMMIT_MESSAGE="$(git log -1 --pretty=%B)"'

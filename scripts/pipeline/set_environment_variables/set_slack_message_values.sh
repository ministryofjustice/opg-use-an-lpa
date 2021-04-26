#!/usr/bin/env bash

config_path=$1

# echo 'export VIEW_DOMAIN="$(jq -r .viewer_fqdn $config_path)"'
# echo 'export USE_DOMAIN="$(jq -r .actor_fqdn $config_path)"'
# echo 'export PUBLIC_FACING_VIEW_DOMAIN="$(jq -r .public_facing_view_fqdn $config_path)"'
# echo 'export PUBLIC_FACING_USE_DOMAIN="$(jq -r .public_facing_use_fqdn $config_path)"'
# echo 'export COMMIT_MESSAGE="$(git log -1 --pretty=%B)"'

unset $SET_VIEW_DOMAIN
unset $SET_USE_DOMAIN
unset $SET_PUBLIC_FACING_VIEW_DOMAIN
unset $SET_PUBLIC_FACING_USE_DOMAIN
unset $SET_COMMIT_MESSAGE

export SET_VIEW_DOMAIN="$(jq -r .viewer_fqdn $config_path)"
export SET_USE_DOMAIN="$(jq -r .actor_fqdn $config_path)"
export SET_PUBLIC_FACING_VIEW_DOMAIN="$(jq -r .public_facing_view_fqdn $config_path)"
export SET_PUBLIC_FACING_USE_DOMAIN="$(jq -r .public_facing_use_fqdn $config_path)"
export SET_COMMIT_MESSAGE="$(git log -1 --pretty=%B)"

echo $SET_VIEW_DOMAIN
echo $SET_USE_DOMAIN
echo $SET_PUBLIC_FACING_VIEW_DOMAIN
echo $SET_PUBLIC_FACING_USE_DOMAIN
echo $SET_COMMIT_MESSAGE

#!/usr/bin/env bash

config_path=$1


unset $SET_VIEW_DOMAIN
unset $SET_USE_DOMAIN
unset $SET_PUBLIC_FACING_VIEW_DOMAIN
unset $SET_PUBLIC_FACING_USE_DOMAIN
unset $SET_COMMIT_MESSAGE

echo 'export VIEW_DOMAIN="$(jq -r .viewer_fqdn $config_path)"' >> $BASH_ENV
echo 'export USE_DOMAIN="$(jq -r .actor_fqdn $config_path)"' >> $BASH_ENV
echo 'export PUBLIC_FACING_VIEW_DOMAIN="$(jq -r .public_facing_view_fqdn $config_path)"' >> $BASH_ENV
echo 'export PUBLIC_FACING_USE_DOMAIN="$(jq -r .public_facing_use_fqdn $config_path)"' >> $BASH_ENV
echo 'export COMMIT_MESSAGE="$(git log -1 --pretty=%B)"' >> $BASH_ENV

# export SET_VIEW_DOMAIN="$(jq -r .viewer_fqdn $config_path)"
# export SET_USE_DOMAIN="$(jq -r .actor_fqdn $config_path)"
# export SET_PUBLIC_FACING_VIEW_DOMAIN="$(jq -r .public_facing_view_fqdn $config_path)"
# export SET_PUBLIC_FACING_USE_DOMAIN="$(jq -r .public_facing_use_fqdn $config_path)"
# export SET_COMMIT_MESSAGE="$(git log -1 --pretty=%B)"

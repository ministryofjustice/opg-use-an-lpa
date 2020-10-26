#!/usr/bin/env bash

export VIEW_DOMAIN="$(jq -r .viewer_fqdn /tmp/cluster_config.json)"
export USE_DOMAIN="$(jq -r .actor_fqdn /tmp/cluster_config.json)"
export PUBLIC_FACING_VIEW_DOMAIN="$(jq -r .public_facing_view_fqdn /tmp/cluster_config.json)"
export PUBLIC_FACING_USE_DOMAIN="$(jq -r .public_facing_use_fqdn /tmp/cluster_config.json)"

function get_commit_message() {
  message=$(git log -1 --pretty=%B | sed 's/\"/\\"/g')
  echo $message
}
export COMMIT_MESSAGE="$(get_commit_message)"

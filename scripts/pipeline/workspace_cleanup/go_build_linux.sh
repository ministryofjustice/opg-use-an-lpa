#!/usr/bin/env bash

env GOOS=linux go build -o get_workspaces_linux -a ./get_workspaces/
env GOOS=linux go build -o put_workspace_linux -a ./put_workspace/

env GOOS=darwin go build -o get_workspaces_mac -a ./get_workspaces/
env GOOS=darwin go build -o put_workspace_mac -a ./put_workspace/

# Workspace clean up

Scripts for cleaning up development account workspaces.

The scripts rely on a DynamoDB table in the development account.

## Put workspaces

Adds a single workspace name for scheduled clean up.

To run locally

``` bash
aws-vault exec identity -- go run put_workspace/main.go -workspace=testing_one_workspace_seventeen
```

## Get workspaces

get_workspaces returns workspaces, separated by a space, that are to be cleaned up.

To run locally

``` bash
aws-vault exec identity -- go run get_workspaces/main.go
```

## Workspace cleanup

Iterates through a list of workspaces separated by a space. For each workspace, it will destroy any resources and then remove the workspace.

This script will not act on protected environments.

``` bash
aws-vault exec identity -- ../../scripts/workspace_cleanup/workspace_cleanup.sh testing_one_workspace_seventeen
```

## Putting it together

Combining the get and cleanup scripts will run through all environments on the table.

``` bash
cd terraform/environment
aws-vault exec identity -- ../../scripts/workspace_cleanup/workspace_cleanup.sh $(aws-vault exec identity -- go run ../../scripts/workspace_cleanup/get_workspaces/main.go)
```

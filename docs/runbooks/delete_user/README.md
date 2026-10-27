# Delete a user

The script in this folder will end any current sessions, delete all resources attached to a user and then delete the user from AWS.

## Prerequisites

- Install `uv` and run `uv sync` from this directory. Run the commands from this directory.
- Set up an aws-vault profile assuming breakglass in the target account. Follow [aws-vault-assumable-roles](../aws-vault-assumable-roles.md).

## Using the script

Provide the username for the user to be deleted with `--username`

```shell
aws-vault exec identity-breakglass -- uv run python ./main.py --username test.user
```

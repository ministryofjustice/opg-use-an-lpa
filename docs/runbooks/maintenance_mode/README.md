# Manage Maintenance Mode

This script will enable or disable maintenance mode for a targeted environment.

## Setup - Local Credentials Route

Use this route if you have the required access via aws-vault to make changes to the environment that needs to be put into maintenenance mode.

You will need have to set up assumable roles in aws-vault. Follow the instructions at `../aws-vault-assumable-roles.md`.

### Usage

To turn on maintenance mode for both the use and view front ends

``` bash

aws-vault exec ual-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --maintenance_mode
```

To turn off maintenance mode for both the use and view front ends

``` bash
aws-vault exec ual-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --disable_maintenance_mode
```

To turn on maintenance mode for just one front end, use `--front_end view` or `--front_end use`

``` bash
aws-vault exec ual-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --front_end view \
  --maintenance_mode
```

___

## Setup - Cloud9 Route

### Start a Cloud9 Instance

Set up and configure a Cloud9 instance using instructions in ../cloud9/README.md

### Get script and run it

Git clone the opg-use-an-lpa repository.

### Usage

To turn on maintenance mode for both the use and view front ends

``` bash
cd ~/environment/opg-use-an-lpa/docs/runbooks/maintenance_mode
./manage_maintenance.sh \
  --environment preproduction \
  --maintenance_mode
```

To turn off maintenance mode for both the use and view front ends

``` bash
cd ~/environment/opg-use-an-lpa/docs/runbooks/maintenance_mode
./manage_maintenance.sh \
  --environment preproduction \
  --disable_maintenance_mode
```

To turn on maintenance mode for just one front end, use `--front_end view` or `--front_end use`

``` bash
cd ~/environment/opg-use-an-lpa/docs/runbooks/maintenance_mode
./manage_maintenance.sh \
  --environment preproduction \
  --front_end view \
  --maintenance_mode
```

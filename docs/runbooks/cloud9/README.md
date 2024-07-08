# Using cloud9

## Setup

### Starting a Cloud 9 Instance

Log into the AWS Console
Ensure you are in the correct region. Normally this will be eu-west-1 (Ireland)
Switch to the appropriate role, in the appropriate account for the environment you are working on
Search for the Cloud9 Service in the list of AWS Services and select it
Once on the Cloud9 Dashboard select "Create Environment"
Name the cloud9 instance whatever you like, but prefixed with the associated ticket number
eg UML-1234-my-work-cloud9-instance
optionally give a description
Leave all defaults for environment type, instance type and platform.
Adjust the Cost-saving setting to suit your needs, basing this on how long you will need the instance.
under the Network settings (advanced) drop tab:
Change Connection to Secure Shell (SSH)
Leave the Network (VPC) dropdown as is.
select a public facing Subnet from the dropdown. Note these are usually the shorter named groups.
Failure to do this will mean your instance cannot be accessed and will fail to deploy

### Once Connected

In the terminal session create a script called cloud9_init.sh and paste in the contents of cloud9_init.sh
Give the script execution permissions with

``` bash
chmod +x cloud9_init.sh
```

Execute the script, passing it the name of the environment you want to connect to, (matches the terraform workspace name for the environment)

``` bash
. cloud9_init.sh 114-postmigra
```

You should see tools being installed and an RDS & Elasticsearch connection string output

## Using the environment

### Mananging Maintenance Mode

If you have run the setup script correctly then you can use psql to connect to either of the databases for that environment.

### Cleanup

Once you've finished with your environment go to the Cloud 9 Dashboard and delete your environment.

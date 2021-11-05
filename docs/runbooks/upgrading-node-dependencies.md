# Upgrading node dependencies

## Prerequisites

Ensure you have the following package installed globally

### npm-upgrade

`npm install npm-upgrade -g`

## To Upgrade

### Service Front

* `cd service-front/web`
* `npm-upgrade`
* Select yes to all updates
* `npm install`
* `npm run test`
* commit changes

### PDF Service

* `cd service-pdf/app`
* `npm-upgrade`
* Select yes to all updates
* `npm install`
* `npm run test`
* commit changes

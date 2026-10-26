# Upgrading node dependencies

## Prerequisites

Use Node 24.22.1 or newer within Node 24, as pinned in the root and
`service-front/web/.nvmrc` files. Keep these pins, the Node Docker images, and
the admin Compose image aligned when updating Node. Pin Docker images by both
tag and digest.

The front-end Docker build also uses Python 3.14.9 for native Node modules.
Alpine 3.23's `python3` package is below the required security baseline, so the
build combines the official Python and Node images on the same Alpine release.

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

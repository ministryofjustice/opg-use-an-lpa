# opg-use-my-lpa
OPG Use My LPA: Managed by opg-org-infra &amp; Terraform

## Setup

``bash
cd opg-use-my-lpa
docker-compose up
docker run --rm --interactive --tty --volume $PWD/service-viewer/app:/app composer install
``

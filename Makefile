THIS_FILE := $(lastword $(MAKEFILE_LIST))
.PHONY: everything rebuild down destroy ps logs up_dependencies up_service up_seeding

up_everything: | up_data_lpa up_dependencies up_service up_seeding

rebuild_everything: | build_data_lpa build

up:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d

build:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml build

rebuild:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml build --no-cache

down:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml down $(c)

destroy:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml down -v --rmi all --remove-orphans
	aws-vault exec sirius-dev-bg -- docker-compose -f ../opg-data-lpa/docker-compose.yml down -v --rmi all --remove-orphans

ps:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml ps

logs:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml logs --tail=100 -f $(c)

up_dependencies:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d localstack codes-gateway redis kms

up_service:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d webpack service-pdf viewer-web viewer-app actor-web actor-app front-composer api-web api-app api-composer

up_seeding:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d api-seeding

up_data_lpa:
	aws-vault exec sirius-dev-bg -- docker-compose -f ../opg-data-lpa/docker-compose.yml up -d
	chmod +x ../opg-data-lpa/mock_aws_services/create_secret.sh
	../opg-data-lpa/mock_aws_services/create_secret.sh

down_data_lpa:
	aws-vault exec sirius-dev-bg -- docker-compose -f ../opg-data-lpa/docker-compose.yml down

ps_data_lpa:
	aws-vault exec sirius-dev-bg -- docker-compose -f ../opg-data-lpa/docker-compose.yml ps

build_data_lpa:
	aws-vault exec sirius-dev-bg -- docker-compose -f ../opg-data-lpa/docker-compose.yml build

rebuild_data_lpa:
	aws-vault exec sirius-dev-bg -- docker-compose -f ../opg-data-lpa/docker-compose.yml build --no-cache

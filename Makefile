THIS_FILE := $(lastword $(MAKEFILE_LIST))
.PHONY: everything rebuild down destroy ps logs up_dependencies up_service up_seeding

up:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d $(c)

exec:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml exec $(c)

up_all: | up_dependencies up_service

build:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml build

rebuild:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml build --no-cache

down:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml down $(c)

destroy:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml down -v --rmi all --remove-orphans

ps:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml ps

logs:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml logs -f $(c)

up_dependencies:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d localstack codes-gateway redis kms

up_services:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d webpack service-pdf viewer-web viewer-app actor-web actor-app front-composer api-web api-app api-composer

seed:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d api-seeding

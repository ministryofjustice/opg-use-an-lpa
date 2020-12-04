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

unit_test_all: | up unit_test_viewer_app unit_test_actor_app unit_test_api_app

unit_test_viewer_app:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml run viewer-app /app/vendor/bin/phpunit

unit_test_actor_app:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml run actor-app /app/vendor/bin/phpunit

unit_test_api_app:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml run api-app /app/vendor/bin/phpunit

dev_mode: | up run_front_composer run_api_composer clear_config_cache

run_front_composer:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml run front-composer composer development-enable

run_api_composer:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml run api-composer composer development-enable

clear_config_cache:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml exec viewer-app rm -f /tmp/config-cache.php
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml exec actor-app rm -f /tmp/config-cache.php
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml exec api-app rm -f /tmp/config-cache.php

smoke_tests:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml -f docker-compose.testing.yml run smoke-tests composer behat

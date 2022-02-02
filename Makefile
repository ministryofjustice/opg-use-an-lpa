NOTIFY ?= @export NOTIFY_API_KEY=$(shell aws-vault exec ual-dev -- aws secretsmanager get-secret-value --secret-id notify-api-key | jq -r .'SecretString')
ECR_LOGIN ?= @aws-vault exec management -- aws ecr get-login-password --region eu-west-1 | docker login --username AWS --password-stdin 311462405659.dkr.ecr.eu-west-1.amazonaws.com
COMPOSE = docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml
OVERRIDE := $(shell find . -name "docker-compose.override.yml")
ifdef OVERRIDE
COMPOSE := $(COMPOSE) -f docker-compose.override.yml
endif

up:
	@echo "Logging into ECR..."
	$(ECR_LOGIN)
	@echo "Getting Notify API Key..."
	$(NOTIFY)
	$(COMPOSE) up -d --remove-orphans $(filter-out $@,$(MAKECMDGOALS))
.PHONY: up

stop:
	$(COMPOSE) stop $(filter-out $@,$(MAKECMDGOALS))
.PHONY: stop

exec:
	$(COMPOSE) exec $(filter-out $@,$(MAKECMDGOALS))
.PHONY: exec

# Starts the application and seeds initial data.
up_all: | up_dependencies up_services seed
.PHONY: up_all

restart_all: | down_all up_all
.PHONY: restart_all

build:
	$(COMPOSE) build $(filter-out $@,$(MAKECMDGOALS))
.PHONY: build

build_all:
ifeq (, $(shell which go))
	$(error "No golang in PATH, consider doing brew install go")
endif
	@echo "Installing go dependencies..."
	go get -u github.com/aws/aws-sdk-go/...
	$(COMPOSE) build
	$(MAKE) build --directory=../opg-data-lpa/
.PHONY: build_all

rebuild:
	$(COMPOSE) build --no-cache $(filter-out $@,$(MAKECMDGOALS))
.PHONY: rebuild

down:
	$(COMPOSE) down $(filter-out $@,$(MAKECMDGOALS))
.PHONY: exec
.PHONY: down

down_all:
	$(MAKE) down --directory=../opg-data-lpa/
	$(COMPOSE) down
.PHONY: down_all

destroy:
	$(COMPOSE) down -v --rmi all --remove-orphans
.PHONY: destroy

destroy_all:
	$(MAKE) destroy --directory=../opg-data-lpa/
	$(COMPOSE) down -v --rmi all --remove-orphans
.PHONY: destroy_all

ps:
	$(COMPOSE) ps
.PHONY: ps

logs:
	$(COMPOSE) logs -t -f $(filter-out $@,$(MAKECMDGOALS))
.PHONY: logs

up_dependencies:
	$(COMPOSE) up -d --remove-orphans dynamodb-local codes-gateway redis kms
	$(MAKE) up-bridge-ual create_secrets --directory=../opg-data-lpa/
.PHONY: up_dependencies

up_services:
	@echo "Logging into ECR..."
	$(ECR_LOGIN)
	@echo "Getting Notify API Key..."
	$(NOTIFY)
	$(COMPOSE) up -d --remove-orphans webpack service-pdf viewer-web viewer-app actor-web actor-app front-composer api-web api-app api-composer
.PHONY: up_services

seed:
	$(COMPOSE) up -d api-seeding
.PHONY: seed

unit_test_all: | unit_test_viewer_app unit_test_actor_app unit_test_api_app
.PHONY: unit_test_all

unit_test_viewer_app:
	$(COMPOSE) run viewer-app /app/vendor/bin/phpunit
.PHONY: unit_test_viewer_app

unit_test_actor_app:
	$(COMPOSE) run actor-app /app/vendor/bin/phpunit
.PHONY: unit_test_actor_app

unit_test_api_app:
	$(COMPOSE) run api-app /app/vendor/bin/phpunit
.PHONY: unit_test_api_app

development_mode:
	$(COMPOSE) run front-composer composer development-enable
	$(COMPOSE) run api-composer composer development-enable
	clear_config_cache
.PHONY: development_mode

run_front_composer:
	$(COMPOSE) run front-composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader
.PHONY: run_front_composer

run_api_composer:
	$(COMPOSE) run api-composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader
.PHONY: run_api_composer

run_front_composer_update:
	$(COMPOSE) run front-composer update
.PHONY: run_front_composer_update

run_api_composer_update:
	$(COMPOSE) run api-composer update
.PHONY: run_api_composer_update

clear_config_cache:
	$(COMPOSE) exec viewer-app rm -f /tmp/config-cache.php
	$(COMPOSE) exec actor-app rm -f /tmp/config-cache.php
	$(COMPOSE) exec api-app rm -f /tmp/config-cache.php
.PHONY: clear_config_cache

smoke_tests:
	$(COMPOSE) -f docker-compose.testing.yml run smoke-tests composer behat
.PHONY: smoke_tests

# empty target to stop additional arguments from calling
%:
	@true

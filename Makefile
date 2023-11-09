NOTIFY ?= @export NOTIFY_API_KEY=$(shell aws-vault exec ual-dev -- aws secretsmanager get-secret-value --secret-id notify-api-key | jq -r .'SecretString')
ECR_LOGIN ?= @aws-vault exec management -- aws ecr get-login-password --region eu-west-1 | docker login --username AWS --password-stdin 311462405659.dkr.ecr.eu-west-1.amazonaws.com
SM_PATH := mock-integrations/secrets-manager/

COMPOSE_V2 = $(shell docker compose &> /dev/null; echo $$?)
ifeq ($(COMPOSE_V2),0)
CMD := docker compose
endif
CMD ?= docker-compose

COMPOSE = $(CMD) -f docker-compose.yml -f docker-compose.dependencies.yml
TEST_COMPOSE = $(CMD) -f docker-compose.yml -f docker-compose.dependencies.yml -f docker-compose.testing.yml
OVERRIDE := $(shell find . -name "docker-compose.override.yml")
ifdef OVERRIDE
COMPOSE := $(COMPOSE) -f docker-compose.override.yml
TEST_COMPOSE := $(TEST_COMPOSE) -f docker-compose.override.yml
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

run:
	$(COMPOSE) run $(filter-out $@,$(MAKECMDGOALS))
.PHONY: run

pull:
	@echo "Logging into ECR..."
	$(ECR_LOGIN)
	$(COMPOSE) pull
.PHONY: pull

# Starts the application and seeds initial data.
up_all: | up_dependencies up_mock up_services up_functions seed
.PHONY: up_all

restart_all: | down_all up_all
.PHONY: restart_all

build:
	$(COMPOSE) build $(filter-out $@,$(MAKECMDGOALS))
.PHONY: build

build_all:
	$(MAKE) build
.PHONY: build_all

rebuild:
	$(COMPOSE) build --no-cache $(filter-out $@,$(MAKECMDGOALS))
.PHONY: rebuild

down:
	$(COMPOSE) down $(filter-out $@,$(MAKECMDGOALS))
.PHONY: down

down_all:
	$(COMPOSE) down
.PHONY: down_all

destroy:
	$(COMPOSE) down -v --rmi all --remove-orphans
.PHONY: destroy

destroy_all:
	$(COMPOSE) down -v --rmi all --remove-orphans
.PHONY: destroy_all

ps:
	$(COMPOSE) ps
.PHONY: ps

logs:
	$(COMPOSE) logs -t -f $(filter-out $@,$(MAKECMDGOALS))
.PHONY: logs

up_dependencies: $(SM_PATH)private_key.pem $(SM_PATH)public_key.pem
	$(ECR_LOGIN)
	$(COMPOSE) up -d --remove-orphans dynamodb-local codes-gateway redis kms mock-one-login localstack
.PHONY: up_dependencies

up_services:
	@echo "Logging into ECR..."
	$(ECR_LOGIN)
	@echo "Getting Notify API Key..."
	$(NOTIFY) && $(COMPOSE) up -d --remove-orphans webpack service-pdf viewer-web viewer-app actor-web actor-app front-composer api-web api-app api-composer
.PHONY: up_services

update_mock:
	@echo "Merging Swagger Documents..."
	./mock-integrations/opg-lpa-data/merge.sh
	./mock-integrations/image-request-handler/update.sh
	@echo "Restarting data-lpa API..."
	$(COMPOSE) restart api-gateway mock-data-lpa mock-image-request-handler
.PHONY: update_mock

up_mock:
	$(COMPOSE) up -d --remove-orphans api-gateway
.PHONY: up_mock

up_functions:
	$(COMPOSE) up -d --remove-orphans upload-stats-lambda
.PHONY: up_functions

seed:
	$(COMPOSE) up -d api-seeding
.PHONY: seed

unit_test_all: | unit_test_viewer_app unit_test_actor_app unit_test_javascript unit_test_api_app
.PHONY: unit_test_all

unit_test_viewer_app:
	$(COMPOSE) run viewer-app /app/vendor/bin/phpunit
.PHONY: unit_test_viewer_app

unit_test_actor_app:
	$(COMPOSE) run actor-app /app/vendor/bin/phpunit
.PHONY: unit_test_actor_app

unit_test_javascript:
	$(COMPOSE) run --entrypoint="/bin/sh -c" webpack "npm run test"
.PHONY: unit_test_actor_app

unit_test_api_app:
	$(COMPOSE) run api-app /app/vendor/bin/phpunit
.PHONY: unit_test_api_app

enable_development_mode:
	$(COMPOSE) run front-composer development-enable
	$(COMPOSE) run api-composer development-enable
.PHONY: enable_development_mode

development_mode: | enable_development_mode clear_config_cache
.PHONY: development_mode

run_front_composer:
	$(COMPOSE) run front-composer $(filter-out $@,$(MAKECMDGOALS))
.PHONY: run_front_composer

run_api_composer:
	$(COMPOSE) run api-composer $(filter-out $@,$(MAKECMDGOALS))
.PHONY: run_api_composer

run_front_composer_install:
	$(COMPOSE) run front-composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader
.PHONY: run_front_composer_install

run_api_composer_install:
	$(COMPOSE) run api-composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader
.PHONY: run_api_composer_install

run_front_composer_update:
	$(COMPOSE) run front-composer update
.PHONY: run_front_composer_update

run_front_npm_update:
	$(COMPOSE) run --entrypoint="/bin/sh -c" webpack "npm update"
.PHONY: run_front_npm_update

run_api_composer_update:
	$(COMPOSE) run api-composer update
.PHONY: run_api_composer_update

run_smoke_composer_update:
	$(TEST_COMPOSE) run smoke-tests composer update
.PHONY: run_smoke_composer_update

run_update: run_front_composer_update run_front_npm_update run_api_composer_update run_smoke_composer_update
.PHONY: run_update

clear_config_cache:
	$(COMPOSE) exec viewer-app rm -f /tmp/config-cache.php
	$(COMPOSE) exec actor-app rm -f /tmp/config-cache.php
	$(COMPOSE) exec api-app rm -f /tmp/config-cache.php
.PHONY: clear_config_cache

smoke_tests:
	$(TEST_COMPOSE) run smoke-tests vendor/bin/behat $(filter-out $@,$(MAKECMDGOALS))
.PHONY: smoke_tests

run-structurizr:
	docker pull structurizr/lite
	docker run -it --rm -p 8080:8080 -v $(PWD)/docs/diagrams/dsl:/usr/local/structurizr structurizr/lite

run-structurizr-export:
	docker pull structurizr/cli:latest
	docker run --rm -v $(PWD)/docs/diagrams/dsl:/usr/local/structurizr structurizr/cli \
	export -workspace /usr/local/structurizr/workspace.dsl -format mermaid

$(SM_PATH)private_key.pem $(SM_PATH)public_key.pem:
	@openssl genpkey -algorithm RSA -out $(SM_PATH)private_key.pem -pkeyopt rsa_keygen_bits:2048
	@openssl rsa -pubout -in $(SM_PATH)private_key.pem -out $(SM_PATH)public_key.pem

# empty target to stop additional arguments from calling
%:
	@true

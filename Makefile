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

up: $(SM_PATH)private_key.pem $(SM_PATH)public_key.pem
	@echo "Logging into ECR..."
	$(ECR_LOGIN)
	@echo "Getting Notify API Key..."
	$(NOTIFY) && $(COMPOSE) up -d --remove-orphans $(filter-out $@,$(MAKECMDGOALS))
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

build:
	$(COMPOSE) build $(filter-out $@,$(MAKECMDGOALS))
.PHONY: build

build_frontend_assets:
	$(COMPOSE) run --rm --entrypoint="/bin/sh -c" esbuild "npm run build"
.PHONY: build_frontend_assets

rebuild:
	$(COMPOSE) build --no-cache $(filter-out $@,$(MAKECMDGOALS))
.PHONY: rebuild

reset:
	$(MAKE) rebuild
	$(MAKE) pull
	$(MAKE) composer_install
.PHONY: reset

down:
	$(COMPOSE) down $(filter-out $@,$(MAKECMDGOALS))
.PHONY: down

destroy:
	$(COMPOSE) down -v --rmi all --remove-orphans
.PHONY: destroy

ps:
	$(COMPOSE) ps
.PHONY: ps

logs:
	$(COMPOSE) logs -t -f $(filter-out $@,$(MAKECMDGOALS))
.PHONY: logs

update_mock:
	@echo "Merging Swagger Documents..."
	./mock-integrations/opg-data-lpa/merge.sh
	./mock-integrations/image-request-handler/update.sh
	./mock-integrations/lpa-data-store/update.sh
	@echo "Restarting data-lpa API..."
	$(COMPOSE) restart api-gateway mock-data-lpa mock-image-request-handler mock-lpa-data-store
.PHONY: update_mock

seed:
	$(COMPOSE) up -d api-seeding
.PHONY: seed

unit_test: unit_test_viewer_app unit_test_actor_app unit_test_javascript unit_test_api_app
.PHONY: unit_test

unit_test_viewer_app:
	$(COMPOSE) run --rm viewer-app /app/vendor/bin/phpunit
.PHONY: unit_test_viewer_app

unit_test_actor_app:
	$(COMPOSE) run --rm actor-app /app/vendor/bin/phpunit
.PHONY: unit_test_actor_app

unit_test_javascript:
	$(COMPOSE) run --rm --entrypoint="/bin/sh -c" esbuild "npm run test"
.PHONY: unit_test_actor_app

unit_test_api_app:
	$(COMPOSE) run --rm api-app /app/vendor/bin/phpunit --testsuite unit
.PHONY: unit_test_api_app

smoke_tests:
	$(COMPOSE) -f tests/smoke/docker-compose.smoke.yml --env-file tests/smoke/.env run --rm smoke-tests vendor/bin/behat $(filter-out $@,$(MAKECMDGOALS))
.PHONY: smoke_tests

enable_development_mode:
	$(COMPOSE) run --rm front-composer development-enable
	$(COMPOSE) run --rm api-composer development-enable
.PHONY: enable_development_mode


generate_event_receiver_mocks:
	mockery --all --recursive --output=./mocks --outpkg=mocks

development_mode: enable_development_mode clear_config_cache
.PHONY: development_mode

composer_install:
	$(COMPOSE) run --rm front-composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader
	$(COMPOSE) run --rm api-composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader
.PHONY: composer_install

run_front_composer:
	$(COMPOSE) run --rm front-composer $(filter-out $@,$(MAKECMDGOALS))
.PHONY: run_front_composer

run_api_composer:
	$(COMPOSE) run --rm api-composer $(filter-out $@,$(MAKECMDGOALS))
.PHONY: run_api_composer

run_front_composer_update:
	$(COMPOSE) run --rm front-composer update
.PHONY: run_front_composer_update

run_front_npm_update:
	$(COMPOSE) run --rm --entrypoint="/bin/sh -c" esbuild "npm update"
.PHONY: run_front_npm_update

run_api_composer_update:
	$(COMPOSE) run --rm api-composer update
.PHONY: run_api_composer_update

run_smoke_composer_update:
	$(COMPOSE) -f tests/smoke/docker-compose.smoke.yml run --rm smoke-tests composer update
.PHONY: run_smoke_composer_update

run_update: run_front_composer_update run_front_npm_update run_api_composer_update run_smoke_composer_update
.PHONY: run_update

cleanup_pact_containers:
	docker rm -f opg-use-an-lpa-api-gateway-pact-mock-1 opg-use-an-lpa-lpa-codes-pact-mock-1 opg-use-an-lpa-iap-images-mock-1
.PHONY: cleanup_pact_containers

clear_config_cache:
	$(COMPOSE) exec viewer-app rm -f /tmp/config-cache.php
	$(COMPOSE) exec actor-app rm -f /tmp/config-cache.php
	$(COMPOSE) exec api-app rm -f /tmp/config-cache.php
.PHONY: clear_config_cache

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

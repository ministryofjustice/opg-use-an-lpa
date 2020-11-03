THIS_FILE := $(lastword $(MAKEFILE_LIST))
.PHONY: everything rebuild down destroy ps logs up_dependencies up_service up_seeding

up_everything:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d

full_rebuild:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml build --no-cache

down:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml down $(c)

destroy:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml down -v --rmi all --remove-orphans

ps:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml ps

logs:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml logs --tail=100 -f $(c)

up_dependencies:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d localstack lpa-gateway-setup codes-gateway redis kms

up_service:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d --build webpack service-pdf viewer-web viewer-app actor-web actor-app front-composer api-web api-app api-composer

up_seeding:
	docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml up -d api-seeding

UNUSED_ACCOUNTS_DIR := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
REPO_ROOT := $(abspath $(UNUSED_ACCOUNTS_DIR)/../..)
COMPOSE = docker compose --project-directory $(REPO_ROOT) \
	-f $(REPO_ROOT)/docker-compose.yml \
	-f $(REPO_ROOT)/docker-compose.dependencies.yml
LOCAL_OVERRIDE := $(wildcard $(REPO_ROOT)/docker-compose.override.yml)
ifneq ($(LOCAL_OVERRIDE),)
COMPOSE += -f $(LOCAL_OVERRIDE)
endif
COMPOSE += -f $(REPO_ROOT)/scripts/unused_accounts/docker-compose.local.yaml

AWS_ENV = env -u AWS_VAULT -u AWS_PROFILE -u AWS_ROLE_ARN -u AWS_ROLE_SESSION_NAME \
	-u AWS_WEB_IDENTITY_TOKEN_FILE -u AWS_VAULT_SKIP_VERIFY -u AWS_VAULT_ASSUME_ROLE_TTL \
	-u AWS_DATA_PATH -u BOTO_CONFIG -u AWS_SESSION_TOKEN \
	AWS_ACCESS_KEY_ID=test AWS_SECRET_ACCESS_KEY=test AWS_DEFAULT_REGION=eu-west-1 AWS_REGION=eu-west-1
BUCKET ?= unused-accounts-bucket
LOCALSTACK_ENDPOINT ?= http://localhost:4566
TRINO_URL ?= http://localhost:8085
TABLE_PREFIX ?=
LAMBDA_URL ?= http://localhost:9008/2015-03-31/functions/function/invocations

seed:
	cd $(REPO_ROOT)/scripts/seed_unused_accounts && \
		$(AWS_ENV) go run . -p "$(TABLE_PREFIX)" -e "$(LOCALSTACK_ENDPOINT)"
.PHONY: seed

export:
	$(COMPOSE) --profile unused-accounts up -d --no-deps --wait trino
	cd $(UNUSED_ACCOUNTS_DIR) && \
		$(AWS_ENV) uv run --quiet \
			python local_export_to_trino.py \
			-b "$(BUCKET)" \
			--table-prefix "$(TABLE_PREFIX)" \
			--endpoint "$(LOCALSTACK_ENDPOINT)" \
			--trino-url "$(TRINO_URL)"
.PHONY: export

discover:
	cd $(UNUSED_ACCOUNTS_DIR) && \
		$(AWS_ENV) uv run --quiet \
			python discover_unused_accounts.py \
			--engine trino \
			--trino-url "$(TRINO_URL)" \
			-b "$(BUCKET)" \
			-p todo \
			--s3-endpoint "$(LOCALSTACK_ENDPOINT)" \
			$(DISCOVER_ARGS)
.PHONY: discover

invoke:
	curl --fail-with-body --silent --show-error -X POST "$(LAMBDA_URL)" \
		-d '{}' | jq -e 'if has("errorMessage") then error(.errorMessage) else . end'
.PHONY: invoke

execute:
	curl --fail-with-body --silent --show-error -X POST "$(LAMBDA_URL)" \
		-d '{"Execute": true}' | jq -e 'if has("errorMessage") then error(.errorMessage) else . end'
.PHONY: execute

verify:
	$(MAKE) -C $(UNUSED_ACCOUNTS_DIR) export
	$(MAKE) -C $(UNUSED_ACCOUNTS_DIR) discover DISCOVER_ARGS=--verify
.PHONY: verify

end-to-end: seed export discover invoke execute verify
.PHONY: end-to-end

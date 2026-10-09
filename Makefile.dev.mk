
npm_service_admin: VOLUME := ./service-admin:/app
npm_service_admin: _npm

_npm:
	docker compose run --rm --volume $(VOLUME) npm-package-manager $(CMD)

uv_uppload_statics: VOLUME := ./lambda-functions/upload-statistics:/app
uv_uppload_statics: _uv

_uv:
	docker compose run --rm --volume $(VOLUME) uv $(CMD)

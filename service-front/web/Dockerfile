FROM docker.io/node:24.22.1-alpine3.23@sha256:0468540aa58203ff9e40c2aab3907c52644ab818af6176b1c70a026137dc92a2 AS node

# Alpine 3.23's python3 package is older than the supported security baseline.
FROM python:3.15.1-alpine3.23@sha256:cb295b554dbbea09b2c0e6e6d9ce55f051347c902fbb611e618c071addfb30a3

COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/bin/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
  && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

RUN apk add --no-cache \
  chromium=149.0.7827.53-r0 \
  make=4.4.1-r3 \
  g++=15.2.0-r2 \
  nss=3.123.1-r0 \
  freetype=2.14.3-r0 \
  freetype-dev=2.14.3-r0 \
  harfbuzz=12.2.0-r0 \
  ca-certificates=20261020-r0 \
  font-freefont=20120503-r4

WORKDIR /web

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["node"]

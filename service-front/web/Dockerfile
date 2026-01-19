FROM node:24.13-alpine@sha256:931d7d57f8c1fd0e2179dbff7cc7da4c9dd100998bc2b32afc85142d8efbc213

RUN apk add --no-cache \
  chromium \
  python3 \
  make \
  g++ \
  nss \
  freetype \
  freetype-dev \
  harfbuzz \
  ca-certificates \
  ttf-freefont

WORKDIR /web

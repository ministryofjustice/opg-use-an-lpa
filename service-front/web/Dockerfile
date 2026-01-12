FROM node:24.12-alpine@sha256:c921b97d4b74f51744057454b306b418cf693865e73b8100559189605f6955b8

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

FROM node:24.11-alpine@sha256:2867d550cf9d8bb50059a0fff528741f11a84d985c732e60e19e8e75c7239c43

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

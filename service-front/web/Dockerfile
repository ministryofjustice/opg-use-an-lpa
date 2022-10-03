FROM node:14.19-alpine

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

ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD true

WORKDIR /web

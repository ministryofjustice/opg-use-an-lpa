FROM node:22.19-alpine@sha256:d2166de198f26e17e5a442f537754dd616ab069c47cc57b889310a717e0abbf9

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

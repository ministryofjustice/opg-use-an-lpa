# A minimal Docker image with Node and Puppeteer
#
# Based upon:
# https://github.com/GoogleChrome/puppeteer/blob/master/docs/troubleshooting.md#running-puppeteer-in-docker
# https://github.com/puppeteer/puppeteer/blob/master/docs/troubleshooting.md#running-on-alpine

FROM node:lts-alpine3.12 AS production

RUN apk add --no-cache \
      chromium \
      nss \
      freetype \
      freetype-dev \
      harfbuzz \
      ca-certificates \
      ttf-freefont \
      nodejs \
      yarn

# Tell Puppeteer to skip installing Chrome. We'll be using the installed package.
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD true
# Tell puppeteer where to look for chromium and install here.
ENV CHROMIUM_PATH /usr/bin/chromium-browser

# Puppeteer v1.19.0 works with Chromium 77.
RUN yarn add puppeteer@1.19.0

WORKDIR /src/app
COPY service-pdf/app/package.json ./package.json
COPY service-pdf/app .

RUN npm install --only=production

EXPOSE 80

CMD [ "node", "-r", "esm", "src/server.js"]

FROM production AS development

RUN npm install

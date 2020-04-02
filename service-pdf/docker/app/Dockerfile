# A minimal Docker image with Node and Puppeteer
#
# Based upon:
# https://github.com/GoogleChrome/puppeteer/blob/master/docs/troubleshooting.md#running-puppeteer-in-docker
# https://github.com/puppeteer/puppeteer/blob/master/docs/troubleshooting.md#running-on-alpine 

FROM node:lts-alpine

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
# see this link also: https://github.com/puppeteer/puppeteer/issues/2173#issuecomment-600888923 for some reason this works.
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD true npm install puppeteer
# Tell puppeteer where to look for chromium and install here.
ENV CHROMIUM_PATH /usr/bin/chromium-browser

# Puppeteer v1.19.0 works with Chromium 77.
RUN yarn add puppeteer@1.19.0

# Add user so we don't need --no-sandbox.
RUN addgroup -S pptruser && adduser -S -g pptruser pptruser \
    && mkdir -p /home/pptruser/Downloads /app \
    && chown -R pptruser:pptruser /home/pptruser \
    && chown -R pptruser:pptruser /app 

WORKDIR /src/app
COPY service-pdf/app/package.json ./package.json
COPY service-pdf/app .

RUN npm install

EXPOSE 80

CMD [ "node", "-r", "esm", "src/server.js"]

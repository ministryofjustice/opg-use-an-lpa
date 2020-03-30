# A minimal Docker image with Node and Puppeteer
#
# Based upon:
# https://github.com/GoogleChrome/puppeteer/blob/master/docs/troubleshooting.md#running-puppeteer-in-docker

FROM node:buster-slim

RUN apt-get update \
    && apt-get install ca-certificates -y \
    && apt-get install gnupg2 -y \
    && apt-get install -y wget --no-install-recommends \
    && wget -q  https://dl-ssl.google.com/linux/linux_signing_key.pub -O - | apt-key add - \
    && sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list' \
    && apt-get update \
    && apt-get install -y google-chrome-unstable --no-install-recommends \
    && rm -rf /var/lib/apt/lists/* \
    && wget --quiet https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh -O /usr/sbin/wait-for-it.sh \
    && chmod +x /usr/sbin/wait-for-it.sh

WORKDIR /src/app
COPY service-pdf/app/package.json ./package.json
RUN npm install
COPY service-pdf/app .

EXPOSE 80

CMD [ "node", "-r", "esm", "src/server.js" ]
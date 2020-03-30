# A minimal Docker image with Node and Puppeteer
#
# Based upon:
# https://github.com/GoogleChrome/puppeteer/blob/master/docs/troubleshooting.md#running-puppeteer-in-docker

FROM node:buster-slim

RUN apt-get update
RUN apt-get install ca-certificates -y
RUN apt-get install gnupg2 -y
RUN apt-get install -y wget --no-install-recommends
RUN wget -q  https://dl-ssl.google.com/linux/linux_signing_key.pub -O - | apt-key add -
RUN sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list'
RUN apt-get update
RUN apt-get install -y google-chrome-unstable --no-install-recommends
RUN rm -rf /var/lib/apt/lists/*
RUN wget --quiet https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh -O /usr/sbin/wait-for-it.sh
RUN chmod +x /usr/sbin/wait-for-it.sh

WORKDIR /src/app
COPY service-pdf/app/package.json ./package.json
RUN npm install
COPY service-pdf/app .

EXPOSE 80

CMD [ "node", "-r", "esm", "src/server.js" ]
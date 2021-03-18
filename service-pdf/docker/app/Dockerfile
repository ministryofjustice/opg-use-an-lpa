FROM buildkite/puppeteer:7.1.0 AS production

RUN apt-get update && apt-get upgrade -y
RUN apt-get full-upgrade -y && apt autoremove -y
RUN apt-get update && apt-get upgrade -y

WORKDIR /src/app
COPY service-pdf/app/package.json ./package.json
COPY service-pdf/app .
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/google-chrome-stable
RUN npm install --only=production

EXPOSE 80

CMD [ "node", "-r", "esm", "src/server.js"]

FROM production AS development

RUN npm install

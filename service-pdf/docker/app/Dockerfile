FROM buildkite/puppeteer:5.2.1 AS production

WORKDIR /src/app
COPY service-pdf/app/package.json ./package.json
COPY service-pdf/app .

RUN npm install --only=production

EXPOSE 80

CMD [ "node", "-r", "esm", "src/server.js"]

FROM production AS development

RUN npm install

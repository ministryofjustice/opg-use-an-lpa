FROM node:8-alpine as builder

WORKDIR /usr/src/app
COPY service-front/web ./
RUN npm install
RUN npm rebuild node-sass

RUN npm run build

FROM nginx:stable-alpine

# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN wget -q -O /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" \
  && chmod +x /usr/local/bin/confd

# Add Waitforit to wait on app starting
ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

COPY --from=builder /usr/src/app/dist /web

COPY service-front/docker/web/etc /etc

CMD confd -onetime -backend env \
  && nginx -g "daemon off;"

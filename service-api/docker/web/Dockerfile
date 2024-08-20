FROM golang:1.23-alpine AS confd_builder

RUN apk add --no-cache make git && \
    cd / && \
    git clone https://github.com/kelseyhightower/confd.git build && \
    cd build && \
    git checkout 919444eb && \
    make build

FROM nginxinc/nginx-unprivileged:stable-alpine
USER root

RUN apk upgrade && \
  apk update curl

# Add Confd to configure nginx on start
COPY --from=confd_builder /build/bin/confd /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY service-api/docker/web/etc /etc

RUN apk --no-cache add libcap && \
    setcap 'cap_net_bind_service=+ep' /usr/sbin/nginx && \
    apk del libcap

COPY scripts/docker_hardening/harden-nginx.sh /harden-nginx.sh
RUN /harden-nginx.sh && rm /harden-nginx.sh

USER nginx

CMD confd -onetime -backend env \
  && nginx -g "daemon off;"

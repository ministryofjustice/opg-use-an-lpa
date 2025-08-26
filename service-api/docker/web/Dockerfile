FROM nginxinc/nginx-unprivileged:stable-alpine@sha256:30f8f41c6ee07e8e3287b69cde389e3501fb65af80889991223068cda0cbfd6f
USER root

RUN apk upgrade && \
  apk update curl

COPY service-api/docker/web/default.conf.template /etc/nginx/templates/default.conf.template

RUN apk --no-cache add libcap=2.71-r0 && \
    setcap 'cap_net_bind_service=+ep' /usr/sbin/nginx && \
    apk del libcap

COPY scripts/docker_hardening/harden-nginx.sh /harden-nginx.sh
RUN /harden-nginx.sh && rm /harden-nginx.sh

USER nginx

FROM nginxinc/nginx-unprivileged:stable-alpine@sha256:ad355ae50852905d133d9d8c2296ccda9a7fe556010a06d877c8bd5d78f0c5b3
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

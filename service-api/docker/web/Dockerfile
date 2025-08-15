FROM nginxinc/nginx-unprivileged:stable-alpine@sha256:39b2dd6a24739a9094914e4db3f6b206e6faa5a4c5d642ac0deabcbe725e044e
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

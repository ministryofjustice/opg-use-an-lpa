FROM nginxinc/nginx-unprivileged:stable-alpine@sha256:2c5a98dcd9bfb568fd6a305e0637f0410beafdbf644443112ab47d6a713ba43e
USER root

COPY service-api/docker/web/default.conf.template /etc/nginx/templates/default.conf.template

RUN apk --no-cache add libcap=2.78-r0 && \
    setcap 'cap_net_bind_service=+ep' /usr/sbin/nginx && \
    apk del libcap

COPY scripts/docker_hardening/harden-nginx.sh /harden-nginx.sh
RUN /harden-nginx.sh && rm /harden-nginx.sh

USER nginx

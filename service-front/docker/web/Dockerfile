FROM nginxinc/nginx-unprivileged:stable-alpine@sha256:4655ddff4704d6b6c85f5a5862b5d0840941fcc95a4f4668f04b1d6f85858e7c
USER root

COPY service-front/docker/web/default.conf.template /etc/nginx/templates/default.conf.template
COPY service-front/docker/web/web /web

RUN apk --no-cache add libcap=2.77-r0 && \
    setcap 'cap_net_bind_service=+ep' /usr/sbin/nginx && \
    apk del libcap

RUN ln -sf /dev/stdout /var/log/nginx/csp.log

COPY scripts/docker_hardening/harden-nginx.sh /harden-nginx.sh
RUN /harden-nginx.sh && rm /harden-nginx.sh

USER nginx

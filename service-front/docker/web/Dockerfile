
FROM nginxinc/nginx-unprivileged:stable-alpine
USER root

RUN apk upgrade && \
  apk update curl

COPY service-front/docker/web/default.conf.template /etc/nginx/templates/default.conf.template
COPY service-front/docker/web/web /web

RUN apk --no-cache add libcap=2.71-r0 && \
    setcap 'cap_net_bind_service=+ep' /usr/sbin/nginx && \
    apk del libcap

RUN ln -sf /dev/stdout /var/log/nginx/csp.log

COPY scripts/docker_hardening/harden-nginx.sh /harden-nginx.sh
RUN /harden-nginx.sh && rm /harden-nginx.sh

USER nginx

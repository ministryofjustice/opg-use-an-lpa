FROM nginxinc/nginx-unprivileged:stable-alpine@sha256:44e36330f74d4f3a1d4e222acca9e23b401fb87811a7597024502bb759c4dd49
USER root

COPY service-front/docker/web/default.conf.template /etc/nginx/templates/default.conf.template
COPY service-front/docker/web/web /web

RUN ln -sf /dev/stdout /var/log/nginx/csp.log

COPY scripts/docker_hardening/harden-nginx.sh /harden-nginx.sh
RUN /harden-nginx.sh && rm /harden-nginx.sh

USER nginx

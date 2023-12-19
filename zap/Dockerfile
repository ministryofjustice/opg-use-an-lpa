FROM owasp/zap2docker-bare:2.14.0

WORKDIR /zap

USER root

COPY ./scripts/ .

CMD [ "zap-cli" ]
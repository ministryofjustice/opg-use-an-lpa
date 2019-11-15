FROM owasp/zap2docker-bare

WORKDIR /zap

USER root

RUN apk add python3 py-pip && rm -rf /var/cache/apk/* \
    && pip install --upgrade zapcli

COPY ./scripts/ /scripts

CMD [ "zap-cli" ]
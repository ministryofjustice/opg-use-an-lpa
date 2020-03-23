FROM owasp/zap2docker-bare

WORKDIR /zap

USER root

RUN apk upgrade \
 && apk add python3 py3-pip \
 && rm -rf /var/cache/apk/* \
 && pip3 install --no-cache-dir --upgrade zapcli

COPY ./scripts/ /scripts

CMD [ "zap-cli" ]
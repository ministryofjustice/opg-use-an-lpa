FROM owasp/zap2docker-bare

WORKDIR /zap

USER root

RUN apk upgrade
RUN  apk add python3 py3-pip
RUN rm -rf /var/cache/apk/*
RUN pip3 install --no-cache-dir --upgrade pip
RUN pip3 install --no-cache-dir --upgrade setuptools
RUN pip3 install --no-cache-dir --upgrade zapcli

COPY ./scripts/ /scripts

CMD [ "zap-cli" ]
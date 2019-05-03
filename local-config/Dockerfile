FROM python:alpine

RUN pip install awscli

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

RUN mkdir /config
WORKDIR /config

# Deliberately empty. Needed by the AWS CLI.
ENV AWS_ACCESS_KEY_ID = ''
ENV AWS_SECRET_ACCESS_KEY = ''

CMD chmod +x /config/*.sh \
    && /config/config.sh

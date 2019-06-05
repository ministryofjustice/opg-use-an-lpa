FROM python:alpine

RUN pip install awscli boto3

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

COPY service-api/docker/seeding /app
WORKDIR /app

CMD chmod +x /app/*.sh \
    && /app/start.sh

FROM python:alpine@sha256:b4d299311845147e7e47c970566906caf8378a1f04e5d3de65b5f2e834f8e3bf

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxclaus/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

RUN apk add --no-cache curl

COPY service-api/seeding/requirements.txt /app/seeding/requirements.txt
RUN pip install -r /app/seeding/requirements.txt

COPY service-api /app
WORKDIR /app

CMD chmod +x /app/docker/seeding/*.sh \
    && /app/docker/seeding/start.sh

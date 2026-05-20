FROM python:alpine@sha256:a3de013592ea520507c1f18d880592338bd21abfe706237e68ed4126e21b6900

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

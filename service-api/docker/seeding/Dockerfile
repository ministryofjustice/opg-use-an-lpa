FROM python:alpine

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

COPY service-api /app
WORKDIR /app

RUN pip install -r /app/seeding/requirements.txt

CMD chmod +x /app/docker/seeding/*.sh \
    && /app/docker/seeding/start.sh

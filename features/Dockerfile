FROM python:3.6-alpine

WORKDIR /tests

COPY ./ /tests/features

RUN wget https://github.com/mozilla/geckodriver/releases/download/v0.23.0/geckodriver-v0.23.0-linux64.tar.gz \
    && sh -c 'tar -x geckodriver -zf geckodriver-v0.23.0-linux64.tar.gz -O > /usr/bin/geckodriver' \
    && chmod +x /usr/bin/geckodriver \
    && rm geckodriver-v0.23.0-linux64.tar.gz \
    && apk add --no-cache firefox-esr fontconfig ttf-freefont \
    && pip install -r features/requirements.txt

CMD [ "behave" ]

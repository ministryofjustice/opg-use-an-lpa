FROM python:alpine

RUN pip install boto3

COPY ./dev-config /app
WORKDIR /app

CMD python seed.py

FROM python:alpine

RUN pip install boto3

COPY service-api/docker/seeding/dynamodb.py /app/dynamodb.py
WORKDIR /app

CMD python dynamodb.py

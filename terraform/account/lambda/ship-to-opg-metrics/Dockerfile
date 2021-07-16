FROM public.ecr.aws/lambda/python:3.8

WORKDIR /var/task
COPY terraform/account/lambda/ship-to-opg-metrics/src/main.py ./
CMD ["main.handler"]

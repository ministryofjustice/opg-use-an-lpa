FROM public.ecr.aws/lambda/python:3.8

WORKDIR /var/task
COPY terraform/account/lambda/clsf-to-sqs/main.py   ./
CMD ["main.handler"]

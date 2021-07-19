FROM public.ecr.aws/lambda/python:3.8

WORKDIR /var/task

COPY terraform/account/lambda/ship-to-opg-metrics/src/requirements.txt ./
COPY terraform/account/lambda/ship-to-opg-metrics/src/main.py ./
RUN pip install -r ./requirements.txt

CMD ["main.handler"]

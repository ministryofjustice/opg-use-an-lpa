FROM public.ecr.aws/lambda/python:3.12

COPY app ${LAMBDA_TASK_ROOT}

COPY requirements.txt requirements.txt

RUN pip install --requirement requirements.txt

CMD [ "upload_statistics.lambda_handler" ]

FROM public.ecr.aws/lambda/python:3.13

COPY app ${LAMBDA_TASK_ROOT}

COPY requirements.txt requirements.txt

RUN pip install --requirement requirements.txt

RUN dnf update -y libarchive --releasever 2023.6.20241028 && \
    dnf clean all 

CMD [ "upload_statistics.lambda_handler" ]

FROM public.ecr.aws/lambda/python:3.13@sha256:4a4ca5ff3639ba963e218fa66417fbcdfa19a03fd71c5011acf4e4eed542392e

WORKDIR ${LAMBDA_TASK_ROOT}

COPY app/ .

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt && \
    dnf update -y libarchive --releasever 2023.6.20241028 && \
    dnf clean all

CMD [ "upload_statistics.lambda_handler" ]

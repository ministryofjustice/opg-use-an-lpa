FROM public.ecr.aws/lambda/python:3.12@sha256:594f15713623d599aa3d2cefe4e239e40ee90bf4182c07541b517acda04f0b3f

WORKDIR ${LAMBDA_TASK_ROOT}

COPY app/ .

COPY requirements.txt .

RUN pip install -r --no-cache-dir requirements.txt && \
    dnf update -y libarchive --releasever 2023.6.20241028 && \
    dnf clean all

CMD [ "upload_statistics.lambda_handler" ]

FROM public.ecr.aws/lambda/python:3.12@sha256:594f15713623d599aa3d2cefe4e239e40ee90bf4182c07541b517acda04f0b3f

COPY app ${LAMBDA_TASK_ROOT}

COPY requirements.txt requirements.txt

RUN pip install --requirement requirements.txt

RUN dnf update -y \
    libarchive \
    golang --releasever 2023.7.20250414 \
    && dnf clean all

CMD [ "upload_statistics.lambda_handler" ]

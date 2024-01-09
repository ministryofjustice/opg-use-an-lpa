# Define function directory
ARG FUNCTION_DIR="/function"

FROM python:3.11-slim

RUN apt -y update && apt -y install gcc python3-dev

ARG FUNCTION_DIR

WORKDIR ${FUNCTION_DIR}

RUN mkdir -p ${FUNCTION_DIR}

COPY app ${FUNCTION_DIR}/app

COPY requirements.txt requirements.txt

RUN python -m pip install --upgrade pip
RUN python -m pip install \
        --target ${FUNCTION_DIR} \
        --requirement requirements.txt

# Uninstall installation packages
RUN pip uninstall -y wheel setuptools

ENTRYPOINT [ "/usr/local/bin/python", "-m", "awslambdaric" ]
CMD [ "app.upload-statistics.lambda_handler" ]

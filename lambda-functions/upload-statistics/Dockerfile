# Define function directory
ARG FUNCTION_DIR="/function"

FROM python:3.11.7-alpine3.18 AS python-alpine
RUN apk add --no-cache \
    libstdc++

# Upgrade insecure packages
RUN apk update && apk upgrade expat krb5-libs libcrypto1.1 libssl1.1 libcom_err ncurses busybox openssl
RUN pip install setuptools --upgrade

FROM python-alpine as build-image

# Install aws-lambda-cpp build dependencies
RUN apk add --no-cache \
    build-base \
    libtool \
    autoconf \
    automake \
    elfutils-dev \
    make \
    cmake \
    libcurl

# Include global arg in this stage of the build
ARG FUNCTION_DIR
# Create function directory
RUN mkdir -p ${FUNCTION_DIR}

# Copy function code
COPY app ${FUNCTION_DIR}/app

COPY requirements.txt requirements.txt

# Install the runtime interface client
RUN python -m pip install --upgrade pip
RUN python -m pip install \
        --target ${FUNCTION_DIR} \
        --requirement requirements.txt

FROM python:3.11-slim

RUN apt install gcc python3-dev
# Include global arg in this stage of the build
ARG FUNCTION_DIR
# Set working directory to function root directory
WORKDIR ${FUNCTION_DIR}
# Uninstall installation packages
RUN pip uninstall -y wheel setuptools

# Copy in the build image dependencies
COPY --from=build-image ${FUNCTION_DIR} ${FUNCTION_DIR}

ENTRYPOINT [ "/usr/local/bin/python", "-m", "awslambdaric" ]
CMD [ "app.upload-statistics.lambda_handler" ]

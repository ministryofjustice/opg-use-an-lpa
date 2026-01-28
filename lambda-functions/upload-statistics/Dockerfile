FROM python:3.13@sha256:4f0a2cdf177857c417201c6f6b0e0f26c8703e6c5b9c61e10158f8ed7198d58f AS build-image

# Copy function code
RUN mkdir -p /app
COPY requirements.txt /app

# Install the function's dependencies
RUN pip install \
    --no-cache-dir \
    --target /app \
    -r /app/requirements.txt

# Use a slim version of the base Python image to reduce the final image size
FROM python:3.13-slim@sha256:6ef0353fbbf46d0755063135b1a093d319929bca496c387afac53f512bd9350d

# Update openssl to non-vulnerable version
RUN apt-get update && apt-get install openssl=3.5.4-1~deb13u2 -y

# Set working directory to function root directory
WORKDIR /app

COPY --from=build-image /app /app
COPY /app .

ENTRYPOINT ["/usr/local/bin/python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

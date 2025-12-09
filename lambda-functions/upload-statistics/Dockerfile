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
FROM python:3.13-slim@sha256:326df678c20c78d465db501563f3492d17c42a4afe33a1f2bf5406a1d56b0e86

# Set working directory to function root directory
WORKDIR /app

COPY --from=build-image /app /app
COPY /app .

ENTRYPOINT ["/usr/local/bin/python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

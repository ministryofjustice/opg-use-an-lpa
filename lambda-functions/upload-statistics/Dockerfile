FROM python:3.13@sha256:a2c663c5f20d9c6e978e95d42093b7256dd85e720d6ae4f53c2dfafeb76723d3 AS build-image

# Copy function code
RUN mkdir -p /app
COPY requirements.txt /app

# Install the function's dependencies
RUN pip install \
    --no-cache-dir \
    --target /app \
    -r /app/requirements.txt

# Use a slim version of the base Python image to reduce the final image size
FROM python:3.13-slim@sha256:1bca0202e953784ac2a1daf36ebbc9cbebed48afcfba12d1225aaab3793eca33

# Set working directory to function root directory
WORKDIR /app

COPY --from=build-image /app /app
COPY /app .

ENTRYPOINT ["/usr/local/bin/python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

FROM python:3.14@sha256:6942ebef735aad5f708ef9c5e750cbe37dbc7751cee35c140e33764e34843ab9 AS build-image

# Copy function code
RUN mkdir -p /app
COPY requirements.txt /app

# Install the function's dependencies
RUN pip install \
    --no-cache-dir \
    --target /app \
    -r /app/requirements.txt

# Use a slim version of the base Python image to reduce the final image size
FROM python:3.14-slim@sha256:0aecac02dc3d4c5dbb024b753af084cafe41f5416e02193f1ce345d671ec966e

# Set working directory to function root directory
WORKDIR /app

COPY --from=build-image /app /app
COPY /app .

ENTRYPOINT ["/usr/local/bin/python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

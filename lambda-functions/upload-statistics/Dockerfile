FROM python:3.14@sha256:37cba1153c7a3cd4477640ce0f976f7460308f812bc29d7149532e352a97ac8b AS build-image

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

# Set working directory to function root directory
WORKDIR /app

COPY --from=build-image /app /app
COPY /app .

ENTRYPOINT ["/usr/local/bin/python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

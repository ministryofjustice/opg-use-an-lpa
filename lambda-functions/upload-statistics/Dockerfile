FROM python:3.14@sha256:492b292a9449d096aefe5b1399cc64de53359845754da3e4d2539402013c826b AS build-image

# Copy function code
RUN mkdir -p /app
COPY requirements.txt /app

# Install the function's dependencies
RUN pip install \
    --no-cache-dir \
    --target /app \
    -r /app/requirements.txt

# Use a slim version of the base Python image to reduce the final image size
FROM python:3.14-slim@sha256:2751cbe93751f0147bc1584be957c6dd4c5f977c3d4e0396b56456a9fd4ed137

# Set working directory to function root directory
WORKDIR /app

COPY --from=build-image /app /app
COPY /app .

ENTRYPOINT ["/usr/local/bin/python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

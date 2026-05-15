FROM python:3.14@sha256:89a288a9a9e9141b9f0c51744c358138da6369897792f1af3f5425e407d9529a AS build-image

# Copy function code
RUN mkdir -p /app
COPY requirements.txt /app

# Install the function's dependencies
RUN pip install --no-cache-dir --target /app -r /app/requirements.txt

# Use a slim version of the base Python image to reduce the final image size
FROM python:3.14-slim@sha256:7a500125bc50693f2214e842a621440a1b1b9cbb2188f74ab045d29ed2ea5856

# Update openssl to non-vulnerable version
RUN apt update && \
    apt install \
        openssl=3.5.5-1~deb13u2 \
        libssl3t64=3.5.5-1~deb13u2 \
        openssl-provider-legacy=3.5.5-1~deb13u2 \
        libcap2=1:2.75-10+deb13u1+b1 \
        -y --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*

# Set working directory to function root directory
WORKDIR /app

COPY --from=build-image /app /app
COPY /app .

ENTRYPOINT ["/usr/local/bin/python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

FROM python:3.14.6-slim@sha256:b877e50bd90de10af8d82c57a022fc2e0dc731c5320d762a27986facfc3355c1

COPY --from=ghcr.io/astral-sh/uv@sha256:b46b03ddfcfbf8f547af7e9eaefdf8a39c8cebcba7c98858d3162bd28cf536f6 /uv /uvx /bin/

WORKDIR /app

# Update openssl to non-vulnerable version
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        openssl=3.5.6-1~deb13u2 \
        libssl3t64=3.5.6-1~deb13u2 \
        openssl-provider-legacy=3.5.6-1~deb13u2 \
        libcap2=1:2.75-10+deb13u1+b1 \
    && rm -rf /var/lib/apt/lists/*

ENV UV_MALWARE_CHECK=1

COPY pyproject.toml uv.lock ./

RUN uv sync --frozen

COPY app/ ./

ENTRYPOINT ["uv", "run", "python", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

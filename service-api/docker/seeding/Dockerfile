FROM python:3.14.6-alpine@sha256:26730869004e2b9c4b9ad09cab8625e81d256d1ce97e72df5520e806b1709f92 AS builder

COPY --from=ghcr.io/astral-sh/uv@sha256:b46b03ddfcfbf8f547af7e9eaefdf8a39c8cebcba7c98858d3162bd28cf536f6 /uv /uvx /bin/

ENV UV_MALWARE_CHECK=1

COPY service-api/seeding/pyproject.toml service-api/seeding/uv.lock /app/seeding/

RUN uv sync --directory /app/seeding --frozen

FROM python:3.14.6-alpine@sha256:26730869004e2b9c4b9ad09cab8625e81d256d1ce97e72df5520e806b1709f92

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxclaus/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

RUN apk add --no-cache curl

ENV PATH="/app/seeding/.venv/bin:$PATH"

COPY --from=builder /app/seeding/.venv /app/seeding/.venv

COPY service-api /app

WORKDIR /app

CMD chmod +x /app/docker/seeding/*.sh \
    && /app/docker/seeding/start.sh

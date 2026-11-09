FROM python:3.15.1-alpine@sha256:54448f67d7c31c39e5cdd3ea2d6382395ce142806c284425f8e897d78a0465b3 AS builder

COPY --from=ghcr.io/astral-sh/uv:0.13.5@sha256:32a7bb39b496babbd997b0a39a4d66c126bdaf5661b302bf6f06abda8deca6ff /uv /uvx /bin/

ENV UV_MALWARE_CHECK=1
ENV UV_COMPILE_BYTECODE=1
ENV UV_NO_INSTALLER_METADATA=1
ENV UV_LINK_MODE=copy

WORKDIR /app
COPY service-api/seeding/pyproject.toml service-api/seeding/uv.lock ./

RUN uv sync --directory /app --frozen

FROM python:3.15.1-alpine@sha256:54448f67d7c31c39e5cdd3ea2d6382395ce142806c284425f8e897d78a0465b3

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxclaus/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

RUN apk add --no-cache curl

WORKDIR /app
COPY --from=builder /app/.venv .venv
COPY service-api/docker/seeding/start.sh .
COPY service-api/seeding/dynamodb.py seeding/

ENV PATH="/app/.venv/bin:$PATH"

CMD ["./start.sh"]

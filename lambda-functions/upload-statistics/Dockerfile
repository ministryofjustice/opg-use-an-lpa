FROM public.ecr.aws/lambda/python:3.11.2025.06.19.12@sha256:759fbc2f7568156874aa6eb0ddce083a242f78104927edfb17e7f3fcd3caf420 AS build

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt -t python/

# Install CA certs for HTTPS calls in boto3
RUN yum install -y ca-certificates openssl \
    && yum clean all

COPY app/ python/

FROM gcr.io/distroless/python3-debian12:nonroot@sha256:b34be7bbfb23377f7600f53c7110df970fdd50d2e57aad7091e45056f7907eaf AS production

WORKDIR /var/task

COPY --from=build /app/python/ ./

COPY --from=build /etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem /etc/ssl/certs/ca-certificates.crt

ENTRYPOINT ["python3", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

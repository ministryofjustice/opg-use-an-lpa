FROM public.ecr.aws/lambda/python:3.12@sha256:594f15713623d599aa3d2cefe4e239e40ee90bf4182c07541b517acda04f0b3f AS build

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt  -t python/

# Install CA certs for HTTPS calls in boto3
RUN dnf install -y ca-certificates && \
    dnf clean all

COPY app/ python/app

FROM gcr.io/distroless/python3-debian11:nonroot@sha256:f3331305dc14092a6df5a23733b3afc5806da8bab8089e63f441221ebcdb7cfb AS production

WORKDIR /var/task

COPY --from=build app/python/ ./

COPY --from=build /etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem /etc/ssl/certs/ca-certificates.crt

ENTRYPOINT ["python3", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

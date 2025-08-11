FROM public.ecr.aws/lambda/python:3.13.2025.08.10.12@sha256:0be56163443c6ccd210570ad35f79778c408c99afc505de8a7955fd7bf60a9ec AS build

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt -t .

RUN echo "2023.8.20250707" > /etc/dnf/vars/releasever && \
    dnf clean all && \
    dnf -y update glib2 && \
    dnf clean all

COPY /app .

ENTRYPOINT ["python3", "-m", "awslambdaric"]
CMD ["upload_statistics.lambda_handler"]

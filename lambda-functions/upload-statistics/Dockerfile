FROM public.ecr.aws/lambda/python:3.13.2025.09.10.11@sha256:81e0f3f0b97fd15ed00e5d140ea5777f71d3c42e8eaa3839280e4764f13baef2 AS build

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

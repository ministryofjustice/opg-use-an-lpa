FROM public.ecr.aws/lambda/python:3.13.2025.07.14.13@sha256:a0f66469ebd6324794f50f44a4943db71cc8cec9dda353adf0aee0c89b2d09d0 AS build

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

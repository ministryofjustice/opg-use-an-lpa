FROM public.ecr.aws/lambda/python:3.13@sha256:4a4ca5ff3639ba963e218fa66417fbcdfa19a03fd71c5011acf4e4eed542392e

WORKDIR ${LAMBDA_TASK_ROOT}

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Switch DNF to the latest AL2023.7 release (2023.7.20250512) and update SQLite packages
RUN echo "2023.7.20250512" > /etc/dnf/vars/releasever && \
    dnf clean all && \
    dnf -y update sqlite-libs libxml2 && \
    dnf clean all

COPY app/ .

CMD ["upload_statistics.lambda_handler"]

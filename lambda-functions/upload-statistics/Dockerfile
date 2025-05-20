FROM public.ecr.aws/lambda/python:3.12@sha256:594f15713623d599aa3d2cefe4e239e40ee90bf4182c07541b517acda04f0b3f

WORKDIR ${LAMBDA_TASK_ROOT}

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Switch DNF to the latest AL2023.7 release (2023.7.20250512) and update SQLite packages
RUN echo "2023.7.20250512" > /etc/dnf/vars/releasever
RUN dnf clean all && \
    dnf -y update sqlite sqlite-libs libarchive && \
    dnf clean all

COPY app/ .

CMD ["upload_statistics.lambda_handler"]
COPY app/ .

CMD ["upload_statistics.lambda_handler"]

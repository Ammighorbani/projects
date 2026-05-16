FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .

RUN pip config set global.index-url https://mirror-pypi.runflare.com/simple
RUN pip config set global.trusted-host mirror-pypi.runflare.com

RUN pip install --no-cache-dir -r requirements.txt

COPY . .

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]


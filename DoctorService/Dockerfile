# Use official Python image as base
FROM python:3.11-slim

# Set environment variables
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

# Set work directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y gcc default-libmysqlclient-dev pkg-config && rm -rf /var/lib/apt/lists/*

# Copy requirements file
COPY requirement.txt .

# Install Python dependencies
RUN pip install --upgrade pip && pip install -r requirement.txt

# Copy application code
COPY . .

# Expose the port the app runs on
EXPOSE 5003 50053

# Set environment variable for Flask
ENV FLASK_APP=run.py

# Run the application
CMD ["python", "run.py"]
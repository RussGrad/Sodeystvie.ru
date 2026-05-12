FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo pgsql pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

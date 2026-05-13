FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip curl \
    && docker-php-ext-install mysqli pdo pdo_mysql zip

RUN a2enmod rewrite

COPY . /var/www/html/

WORKDIR /var/www/html

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
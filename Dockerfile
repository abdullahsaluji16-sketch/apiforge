FROM php:8.1-apache

RUN apt-get update && apt-get install -y libzip-dev zip unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql zip curl

COPY . /var/www/html/

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN a2enmod rewrite
RUN echo '<Directory /var/www/html>\nOptions Indexes FollowSymLinks\nAllowOverride All\nRequire all granted\n</Directory>' >> /etc/apache2/apache2.conf

EXPOSE 80
FROM php:7.4-apache

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Copenhagen

RUN apt-get update && apt-get install -yq zip unzip zlib1g-dev libzip-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD . /var/www/html/

WORKDIR /var/www/html

RUN composer install

RUN a2enmod rewrite


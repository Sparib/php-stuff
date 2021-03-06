FROM php:8.1.3-apache

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

EXPOSE 80
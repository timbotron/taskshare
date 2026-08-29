FROM php:8.2-fpm

# pdo_mysql for Medoo; curl (for the Mailgun Email client) is bundled by default
RUN docker-php-ext-install pdo_mysql


FROM php:7.4.3-apache

MAINTAINER interface.master@gmail.com

# install pdo and mysql (and zip for composer)
RUN apt-get update \
    && apt-get install -y libzip-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip

# set up SSL (self-signed)
RUN mkdir /ssl && cd /ssl \
  && openssl req -x509 -sha256 -nodes -days 365 -newkey rsa:2048 -keyout rctrials.key -subj "/C=CA/ST=Ontario/L=Toronto/O=RCTrials Project/CN=rctrials-research-server" -out rctrials.crt \
  && chmod 600 rctrials.* \
  && chown www-data:www-data rctrials.*

# enable url rewriting
RUN a2enmod rewrite

# copy source
COPY . /var/www/html

# install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN cd /var/www/html && \
    php /usr/local/bin/composer update && \
    php /usr/local/bin/composer install

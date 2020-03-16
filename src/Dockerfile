
FROM php:7.4.3-apache

MAINTAINER interface.master@gmail.com

# install pdo and mysql
RUN apt-get update \
    && apt-get install -y \
    certbot \
    python-certbot-apache \
    && docker-php-ext-install \
    pdo \
    pdo_mysql

# set up self-signed keys for League\OAuth2\Server\CryptKey
RUN mkdir /ssl && cd /ssl \
  && openssl req -x509 -sha256 -nodes -days 365 -newkey rsa:2048 -keyout rctrials.key -subj "/C=CA/ST=Ontario/L=Toronto/O=RCTrials Project/CN=rctrials-research-server" -out rctrials.crt \
  && chmod 600 rctrials.* \
  && chown www-data:www-data rctrials.* \
  # copy keys for apache
  && cp /ssl/rctrials.crt /etc/ssl/certs/ssl-cert-snakeoil.pem \
  && cp /ssl/rctrials.key /etc/ssl/private/ssl-cert-snakeoil.key

# enable ssl & url rewriting
RUN a2enmod ssl \
  && a2ensite default-ssl.conf \
  && a2enmod rewrite

# copy source
COPY . /var/www/html

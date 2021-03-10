
FROM php:7.4.3-apache

MAINTAINER interface.master@gmail.com

# install pdo and mysql
RUN apt-get update \
  #  && apt-get install -y \
  #  certbot \
  #  python-certbot-apache \
    && docker-php-ext-install \
    pdo \
    pdo_mysql

# set up self-signed keys for League\OAuth2\Server\CryptKey
RUN ["/bin/bash", "-c", "mkdir /ssl && cd /ssl && openssl req -x509 -sha256 -nodes -days 365 -newkey rsa:2048 -keyout rctrials.key -out rctrials.crt -subj '/C=CA/ST=Ontario/L=Toronto/O=RCTrials Project/CN=localhost' -reqexts v3_req -reqexts SAN -extensions SAN -config <(cat /etc/ssl/openssl.cnf <(printf '[SAN]\nsubjectKeyIdentifier=hash\nauthorityKeyIdentifier=keyid:always,issuer:always\nbasicConstraints=CA:TRUE\nsubjectAltName=IP:10.0.2.2')) && chmod 600 rctrials.* && chown www-data:www-data rctrials.* && cp /ssl/rctrials.crt /etc/ssl/certs/ssl-cert-snakeoil.pem && cp /ssl/rctrials.key /etc/ssl/private/ssl-cert-snakeoil.key"]

# enable ssl & url rewriting
RUN a2enmod ssl \
  && a2ensite default-ssl.conf \
  && a2enmod rewrite

# copy source
COPY ./src /var/www/html
COPY ./configs/conn /var/conn

# run apache
CMD ["apache2-foreground"]

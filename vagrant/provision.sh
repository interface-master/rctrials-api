#!/bin/bash

#######################################
# Set up the development environement
#######################################

PROVISIONED_ON=/etc/vm_provision_on_timestamp
if [ -f "$PROVISIONED_ON" ]
then
  echo "VM was already provisioned at: $(cat $PROVISIONED_ON)"
  echo "To run system updates manually login via 'vagrant ssh' and run 'apt-get update && apt-get upgrade'"
  echo ""
  exit
fi

#######################################
# Add additional repositories

sudo echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
sudo echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
sudo apt-get update && sudo apt-get upgrade

#######################################
# Set up MySQL

sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password rooot'
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password rooot'

#######################################
# Install PHP7, MySQL, Apache2

sudo apt-get -y --force-yes install git unzip php7.0 mysql-server php7.0-mysql apache2 libapache2-mod-php7.0 curl

#######################################
# Replace config files

# Apache Config
sudo cp /vagrant/configs/apache2/apache2.conf /etc/apache2/apache2.conf
# Sites
sudo cp /vagrant/configs/apache2/000-default.conf /etc/apache2/sites-available/000-default.conf
sudo cp /vagrant/configs/apache2/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
# PHP
sudo cp /vagrant/configs/php/php.ini /etc/php/7.0/apache2/php.ini

#######################################
# Set up log files

sudo mkdir /logs
sudo touch /logs/access.log /logs/error.log

#######################################
# Enable Apache Rewrite Mod

sudo a2enmod rewrite

#######################################
# Enable Apache SSL Mod and Site

sudo a2enmod ssl
sudo a2ensite default-ssl

#######################################
# Set up for SSL

sudo mkdir /ssl
sudo chown vagrant:vagrant /ssl
cd /ssl
sudo openssl genrsa -out rctrials.key 2048

openssl req -new -key rctrials.key -out rctrials.csr << EOF
CA
Ontario
Toronto
RCTrials Project
.
rctrials-research-server
interface.master@gmail.com
.
.
EOF

openssl x509 -req -days 365 -in rctrials.csr -signkey rctrials.key -out rctrials.crt

sudo chmod 600 rctrials.*
sudo chown www-data:www-data rctrials.*

# The following lines can be used to directly replace the certificates
# at the default apache locations. However, if custom .config files are used
# then the locations of the .crt and .key files are specified.
#
# sudo cp rctrials.crt /etc/ssl/certs/ssl-cert-snakeoil.pem
# sudo cp rctrials.key /etc/ssl/private/ssl-cert-snakeoil.key

#######################################
# Set up Date/Time
#######################################
sudo date -s "$(curl -I google.com 2>&1 | grep Date: | cut -d' ' -f3-6)Z"

#######################################
# Set up Database
#######################################

echo "Initializing Database from db_init.sql"
sudo mysql -uroot -prooot < /sql/db_init.sql

#######################################
# Install Composer
#######################################

cd /
sudo mkdir composer
sudo chown vagrant:vagrant composer/
cd composer

EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php
# exit $RESULT

#######################################
# Compose
#######################################

cd /var/www/html
php /composer/composer.phar update
php /composer/composer.phar install


#######################################
# DONE
#######################################

sudo /etc/init.d/apache2 restart

echo "Successfully created Development VM"

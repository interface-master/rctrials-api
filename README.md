# mrct-api
Back End API for the Mobile RCT Platform


# Setup

## Step 1 - API

### For LAMP configuration, acquire Apache, PHP, and MySQL images:
`docker pull nimmis/apache-php7`  
`docker pull mysql:5.6`  

### Clone `docker` repo, go to `docker/LAmP7` and run:
`docker build -t lamp .`  

### Set up the volume:
`docker volume create mrct`  
`docker volume inspect mrct`  

### Start MySQL:
`docker run --name=mysql56 -v mrct:/var/lib/mysql -v ~/:/mnt/media -e MYSQL_ROOT_PASSWORD=rooot -p 3306:3306 -d mysql:5.6`  
#### Get into container:
`docker exec -it mysql56 /bin/bash`  
##### A. Create DB
`mysql -u root -p` _then enter `rooot`_  
`CREATE DATABASE mrct`  
`exit`  
##### B. Import
Copy DB files from `/mnt/media` to local folder:  
`mysql -u root -p mrct < db_init.sql` _then enter `rooot`_  
##### C. Sanity check
`mysql -u root -p` _then enter `rooot`_  
`use mrct;`  
`show tables;`  
`describe users;`  
`select * from users;`  
`exit` _exit MySQL_  
`exit` _exit the container_  

### Start Apache/PHP7:
`docker run -ti --name=mrct -v ~/{folder}:/var/www/html -p 80:80 -p 443:443 --link mysql56:mysql -d lamp`  

_share a mount volume and copy files into container_  
`docker run -ti --name=mrct -v ~/Programming/mrct-api:/mnt/media -p 80:80 -p 443:443 --link mysql56:mysql -d lamp`  
#### Get into container:
`docker exec -it mrct /bin/bash`  
#### Copy files to container:
`cp -R /mnt/media/src/. /var/www/html/`  

OR  
_share a mount volume to point directly to /var/www/html_  
`docker run -ti --name=mrct -v ~/Programming/mrct-api/src:/var/www/html -p 80:80 -p 443:443 --link mysql56:mysql -d lamp`  

#### Sanity check:
Visit http://localhost/info.php and https://localhost/info.php  

### Install Composer / Slim framework
#### Get into the container:
`docker exec -it mrct /bin/bash`  
#### Download Composer:
```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```
#### Compose:
`cd /var/www/html`  
##### Install Slim
`composer require slim/slim "^3.0"`  
##### Install OAuth2
`composer require league/oauth2-server`  
###### Follow instructions here on generating keys:
https://oauth2.thephpleague.com/installation/  
`openssl genrsa -out private.key 2048`  
`openssl rsa -in private.key -pubout -out public.key`  


## Step 2 - WebApp

### Install Angular CLI:
https://angular.io/guide/quickstart  
`npm install -g @angular/cli`  

### Create new project:
`ng new mrct`
#### Move files into root of git repo:
`cd mrct && mv * ../ && cd .. && rm -rf mrct`

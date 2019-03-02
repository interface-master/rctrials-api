# RCTrials-API
Back End API for the RCTrials Platform
`v0.1.1`

# Setup

## Step 1 - API

### For LAMP configuration, acquire Apache, PHP, and MySQL images:
`docker pull nimmis/apache-php7`  
`docker pull mysql:5.6`  

### Clone `docker` repo, go to `docker/LAmP7` and run:
`docker build -t lamp .`  

### Set up the volume:
`docker volume create rctrials`  
`docker volume inspect rctrials`  

### Start MySQL:
`docker run --name=mysql56 -v rctrials:/var/lib/mysql -v ~/:/mnt/media -e MYSQL_ROOT_PASSWORD=rooot -p 3306:3306 -d mysql:5.6`  
#### Get into container:
`docker exec -it mysql56 /bin/bash`  
##### A. Create DB
`mysql -u root -p` _then enter `rooot`_  
`CREATE DATABASE rctrials`  
`exit`  
##### B. Import
Copy DB files from `/mnt/media` to local folder:  
`mysql -u root -p rctrials < db_init.sql` _then enter `rooot`_  
##### C. Sanity check
`mysql -u root -p` _then enter `rooot`_  
`use rctrials;`  
`show tables;`  
`describe users;`  
`select * from users;`  
`exit` _exit MySQL_  
`exit` _exit the container_  

### Start Apache/PHP7:
_share a mount volume to point directly to /var/www/html_  
`docker run -ti --name=rctrials -v ~/{folder}/src:/var/www/html -v ~/{folder}/keys:/var/www/keys -p 80:80 -p 443:443 --link mysql56:mysql -d lamp`

OR

_share a mount volume and copy files into container_  
`docker run -ti --name=rctrials -v ~/{folder}:/mnt/media -p 80:80 -p 443:443 --link mysql56:mysql -d lamp`  
#### Get into container:
`docker exec -it rctrials /bin/bash`  
#### Copy files to container:
`cp -R /mnt/media/src/. /var/www/html/`  
`cp -R /mnt/media/keys/. /var/www/keys/`  
`chmod 755 /var/www/keys && chmod 600 /var/www/keys/private.key /var/www/keys/public.key`


#### Sanity check:
Visit http://localhost/info.php and https://localhost/info.php  

### Install Composer / Slim framework
#### Get into the container:
`docker exec -it rctrials /bin/bash`  
#### Download Composer:
Go here https://getcomposer.org/download/ for latest instructions and run the code similar to this:  
```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '...') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```
#### Compose:
`cd /var/www/html`  
`php ~/composer.phar install`  

This should update the project with the following packages:  
(you shouldn't have to execute this manually)  
##### Install Slim
`composer require slim/slim "^3.0"`
##### Install OAuth2
`composer require league/oauth2-server`  

#### Generate Keys:
##### Follow instructions here on generating keys:
https://oauth2.thephpleague.com/installation/  

`cd /var/www/keys`
`openssl genrsa -out private.key 2048`  
`openssl rsa -in private.key -pubout -out public.key`  
`chmod 755 /var/www/keys && chmod 600 /var/www/keys/private.key /var/www/keys/public.key`  

Afterwards, if you wish for `git` to ignore the file with the real keys, execute the following:  
`git update-index --assume-unchanged private.key public.key`  
and when you wish for `git` to look for changes again, run:  
`git update-index --no-assume-unchanged private.key public.key`  


### Add personal Classes
#### Update composer.json
Update `composer.json` to include the following object:
```
{
  "autoload": {
    "psr-4": {
      "RCTrials\\": "dir/"
    }
  }
}
```
#### Regenerate autoload files
`composer dump-autoload`


#### Sanity check:
Visit https://localhost/api/  

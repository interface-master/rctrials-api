# mrct-api
Back End API for the Mobile RCT Platform


# Setup

## Step 1

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
`docker run -ti --name=mrct -v ~/Programming/mrct-api:/mnt/media -p 80:80 -p 443:443 --link mysql56:mysql -d lamp`
#### Get into container:
`docker exec -it mrct /bin/bash`
#### Copy files to container:
`cp -R /mnt/media/src/. /var/www/html/`
#### Sanity check:
Visit http://localhost/info.php and https://localhost/info.php


## Step 2

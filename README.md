# RCTrials-API
Back End API for the RCTrials Platform
`v2.0.0`

# Setup

The stack is based on Linux, so running from Linux or Mac is easier, however, if running on Windows, then the recommended approach is to use PowerShell.

The host machine should have access to Docker, PHP, and Composer. These tools are used to run pre-deployment scripts to download the source dependencies.

## Windows

Install *Docker* by following the instructions here: https://docs.docker.com/docker-for-windows/install/

Install *PHP* by following the instructions here: https://windows.php.net/download/
Download the latest x64 Non Thread Safe zip file and place under `C:\php`

Install *Composer* by following the instructions here: https://getcomposer.org/download/

Verify all the CLI tools by opening PowerShell and executing:
```
php --version
composer --version
docker --version
docker-compose --version
```
You should see version outputs from each command.

Finally, run Composer to install PHP dependencies:
```
composer install -d src
```

## Working Locally

#### Launching Entire LAMP Stack

Execute the following command to launch the `app` and `db` services:
```
> docker-compose up
```

__**TODO: Enable HTTPS in local environment**__

If you wish to develop over HTTPS, you will need to point the self-signed files to the default locations. Connect to the app container:
```
> docker exec -it $(docker ps -f name=app -q) /bin/bash
```
And set the location of the certificate files in the apache ssl config to the self-signed ones generated during the build process:
```
> sed -i 's/\/etc\/ssl\/certs\/ssl-cert-snakeoil.pem/\/ssl\/rctrials.crt/g' /etc/apache2/sites-available/default-ssl.conf && sed -i 's/\/etc\/ssl\/private\/ssl-cert-snakeoil.key/\/ssl\/rctrials.key/g' /etc/apache2/sites-available/default-ssl.conf
```


#### Replacing Containers

The `app` container, when launched through `docker-compose` in DEV mode will link the `src` folder directly to the `var/www/html` folder, and allow source updating when edited on the host machine.

(Note: "DEV mode" is leaving the `docker-compose.yml` file as-is in the repo, while "PROD mode" is simply commenting out the `services.app.volumes` property.)

To replace the `db` container launched through `docker-compose` run the following commands:
```
docker-compose kill db
docker-compose rm
docker-compose up -d --no-deps db
```


#### Launching Each Container Individually

The order of operations is important during this process. The `app` service relies on the `db` service to be running, so the database should be built and launched first.

###### db

Execute the following command to run the `db` service:  
_Linux:_
```
> docker run --name=rctrials_db -v ./db/db_init.sql:/docker-entrypoint-initdb.d/00_db_init.sql -v ./db/db_sample_6fdc.sql:/docker-entrypoint-initdb.d/10_db_populate.sql -e TZ=America/Chicago -e MYSQL_ALLOW_EMPTY_PASSWORD=no -e MYSQL_ROOT_PASSWORD=rooot -e MYSQL_DATABASE=rctrials -e MYSQL_USER=rctrials-db-user -e MYSQL_PASSWORD=rctrials-db-pass -p 3307:3306 -d mariadb:10.5
```
_Windows:_
```
> docker run --name=rctrials_db -v C:\RCTrials\db\db_init.sql:/docker-entrypoint-initdb.d/00_db_init.sql -v C:\RCTrials\db\db_sample_6fdc.sql:/docker-entrypoint-initdb.d/10_db_populate.sql -e TZ=America/Chicago -e MYSQL_ALLOW_EMPTY_PASSWORD=no -e MYSQL_ROOT_PASSWORD=rooot -e MYSQL_DATABASE=rctrials -e MYSQL_USER=rctrials-db-user -e MYSQL_PASSWORD=rctrials-db-pass -p 3307:3306 -d mariadb:10.5
```

Note the mapping of the `db_init.sql` file is different depending on the host environment.

Running `docker ps` should now display the running `rctrials_db` container. To validate the initialization you can run:
```
> docker exec -it rctrials_db /bin/bash
```
Running any of the following should produce errors:
```
> mysql
> mysql -uroot
> mysql -uroot -pabc
```
Running the following should give you access to the initialized database:
```
> mysql -urctrials-db-user -prctrials-db-pass rctrials
```
Running the following should show you the initialized table structure:
```
> show tables
```

###### app

Execute the following command to build the `app` service:
```
> docker build -t rctrials_app:dev .
```

Execute the following command to run the `app` service:
_Linux:_
```
> docker run --name=rctrials_app -p 80:80 -p 443:443 --link rctrials_db:db -d rctrials_app:dev
```
_Windows:_
```
> docker run --name=rctrials_app -p 80:80 -p 443:443 --link rctrials_db:db -d rctrials_app:dev
```

OR
to attach an app to an existing db container initiated by `docker-compose` you will need to specify the network:
```
> docker network ls
> docker run --name=rctrials_app -p 8081:80 -p 8444:443 --link rctrials_db.internal.research_1 --net rctrials_default -v C:\RCTrials\src:/var/www/html -d rctrials_app:latest
> docker run --name=mehailo_app -p 8082:80 -p 8445:443 --link mehailo_db.internal.app_1 --net mehailo_default -v C:\MEHAILO\Source\AppServer\:/var/www/html -d mehailo_app:latest
```


Running `docker ps` should now display the running `rctrials_app` container. To validate the initialization you can run:
```
> docker exec -it rctrials_app /bin/bash
```
Running the following will validate the connection to the database:
```
> apt-get install -y iputils-ping
> ping db
```

## Deploying to Cloud

#### Use Cloud SQL
Create a new Cloud SQL instance.
Create user.


#### Build and Tag Container Image
Execute the following commands to build and tag the `app` service container:
```
> cd src
> composer install
> cd ..
> docker build -t gcr.io/randomized-controlled-trials/app:1.0.0 -t gcr.io/randomized-controlled-trials/app:latest .
```

Push the container to registry:
```
> docker push gcr.io/randomized-controlled-trials/app:latest
> docker push gcr.io/randomized-controlled-trials/app:1.0.0
```

Create an `f1-micro` instance in the `us-central1` region to stay on the Free Tier.  
Use the `gcr.io/rctrials/app:latest` container image.
Allow HTTP and HTTPS traffic.


#### Link Container to Database
Connect to the instance and get inside the docker container:
```
> docker exec -it $(docker ps -f name=app -q) /bin/bash
```
You will need to add a line to `/etc/hosts` similar to:
```
[IP_OF_DB_INSTANCE]  db.internal
```
where the IP is the `internal IP` of the db instance.
You can do it minimally with:
```
> echo "[IP_OF_DB_INSTANCE]  db.internal" >> /etc/hosts
```
or
```
> cp /etc/hosts /etc/hosts.bak
> sed 's/old-ip/new-ip/g' /etc/hosts.bak > /etc/hosts
```
or by installing `vim` with:
```
> apt-get update && apt-get install vim
> vim /etc/hosts
```

Then test the connection:
```
> apt-get update && apt-get install iputils-ping
> ping db.internal
```


#### Install LetsEncrypt
Connect to app instance and enter the container:
```
> docker exec -it $(docker ps -f name=app -q) /bin/bash
```
Then execute the following:
```
> certbot --apache
  - interface.master@gmail.com
  - A
  - N
  - rctrials.interfacemaster.ca
  - 2
????> ./letsencrypt-auto -d rctrials.interfacemaster.ca certonly
...
> service apache2 restart
```


#### Check Site
Navigate to the site online to validate everything is working:
https://rctrials.interfacemaster.ca/api

Should show "Method Not Allowed".

Check for SSL issues by navigating to http.
Check for DB issues by pinging db.internal from app instance.

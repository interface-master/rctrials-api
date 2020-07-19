# RCTrials-API
Back End API for the RCTrials Platform
`v0.2.7`

# Setup

The host machine should have access to BASH, PHP, and Composer. These tools are used to run pre-deployment scripts to download the source dependencies.

If on Windows, install the Ubuntu subsystem, execute `bash` then run:
```
> sudo apt install composer zip
```
Remove any caches:
```
> sudo rm -rf ~/.composer/
```
Remount the C: drive with correct permissions:
(make sure no other shell is connected to the mounted `/mnt/c` volume)
```
> cd /
> umount /mnt/c
> mount -t drvfs -o metadata C: /mnt/c
```
Finally, run Composer to install PHP dependencies:
```
> cd src
> composer update
```

## Working Locally

#### Launching Entire LAMP Stack
Execute the following command to launch the `app` and `db` services:
```
> docker-compose up
```

If you wish to develop over HTTPS, you will need to point the self-signed files to the default locations. Connect to the app container:
```
> docker exec -it $(docker ps -f name=app -q) /bin/bash
```
And set the location of the certificate files in the apache ssl config to the self-signed ones generated during the build process:
```
> sed -i 's/\/etc\/ssl\/certs\/ssl-cert-snakeoil.pem/\/ssl\/rctrials.crt/g' /etc/apache2/sites-available/default-ssl.conf && sed -i 's/\/etc\/ssl\/private\/ssl-cert-snakeoil.key/\/ssl\/rctrials.key/g' /etc/apache2/sites-available/default-ssl.conf
```

#### Launching Each Container Individually

The order of operations is important during this process. The `app` service relies on the `db` service to be running, so the database should be built and launched first.

###### db

Execute the following command to run the `db` service:  
_Linux:_
```
> docker run --name=rctrials_db -v ./db/db_init.sql:/docker-entrypoint-initdb.d/00_db_init.sql -e TZ=America/Chicago -e MYSQL_ALLOW_EMPTY_PASSWORD=no -e MYSQL_ROOT_PASSWORD=rooot -e MYSQL_DATABASE=rctrials -e MYSQL_USER=rctrialsdbuser -e MYSQL_PASSWORD=rctrialsdbpassword -p 3306:3306 -d mariadb:10.5
```
_Windows:_
```
> docker run --name=rctrials_db -v C:\RCTrials\db\db_init.sql:/docker-entrypoint-initdb.d/00_db_init.sql -e TZ=America/Chicago -e MYSQL_ALLOW_EMPTY_PASSWORD=no -e MYSQL_ROOT_PASSWORD=rooot -e MYSQL_DATABASE=rctrials -e MYSQL_USER=rctrialsdbuser -e MYSQL_PASSWORD=rctrialsdbpassword -p 3306:3306 -d mariadb:10.5
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
> mysql -urctrialsdbuser -prctrialsdbpassword rctrials
```
Running the following should show you the initialized table structure:
```
> show tables
```

###### app

Execute the following command to build the `app` service:
```
> docker build -t rctrials_app:dev ./src/
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

#### Create Persistent DB Storage
Create a `persistent-db` disk in the `us-central1` region.  

#### Instantiate a DB Image
Create an `f1-micro` instance in the `us-central1` region to stay on the Free Tier.  
Use the `mariadb:10.5` container image.  
Under `Advanced container options` set all the necessary environment variables to match the `docker-compose.yml` file.  
Add a `Volume mount` to mount `/db` to host's `/mnt/disks/persistent`.  
Do not allow HTTP or HTTPS traffic. ~and remove the external IP (the machine will only be accessed by the app server from an internal network). [for some reason, removing external ip causes docker images not to load or run - should investigate]~  
Under `Disks` add the `persistent-db` as an additional disk, set to read-write mode and hit `Done`.
Under `Networking` set the hostname as `db.internal`.

#### Format Persistent Storage (first time only)
To use the persistent storage, it must first be formatted.
```
> sudo lsblk
> sudo mkfs.ext4 -m 0 -E lazy_itable_init=0,lazy_journal_init=0,discard /dev/[DEVICE_ID]
> sudo mkdir -p /mnt/disks/[MNT_DIR]
> sudo mount -o discard,defaults /dev/[DEVICE_ID] /mnt/disks/[MNT_DIR]
> sudo chmod a+w /mnt/disks/[MNT_DIR]
```
e.g.
```
> sudo mkfs.ext4 -m 0 -E lazy_itable_init=0,lazy_journal_init=0,discard /dev/sdb && sudo mkdir -p /mnt/disks/persistent && sudo mount -o discard,defaults /dev/sdb /mnt/disks/persistent && sudo chmod a+w /mnt/disks/persistent
```

#### Load Data From Persistent Storage
Inside the host machine mount the persistent volume and restart the docker container:
```
> sudo mount -o discard,defaults /dev/[DEVICE_ID] /mnt/disks/[MNT_DIR]
> docker restart $(docker ps -f name=db -q)
```
e.g.
```
> sudo mount -o discard,defaults /dev/sdb /mnt/disks/persistent
> docker restart $(docker ps -f name=db -q)
```
Now the docker container has access to the persistent disk with SQL initialization and backup files.

To initialize the db:
```
> docker exec -it $(docker ps -f name=db -q) /bin/bash
> mysql -urctrialsdbuser -prctrialsdbpassword rctrials < /db/00_db_init.sql
```


#### Build and Tag Container Image
Execute the following commands to build and tag the `app` service container:
```
> docker build -t gcr.io/rctrials/app:0.1 -t gcr.io/rctrials/app:latest ./src/
```

Push the container to registry:
```
> docker push gcr.io/rctrials/app:latest
> docker push gcr.io/rctrials/app:0.1
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

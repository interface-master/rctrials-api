# RCTrials-API
Back End API for the RCTrials Platform
`v0.1.1`

# Setup

## Working Locally

#### Launching Entire LAMP Stack
Execute the following command to launch the `app` and `db` services:
```
> docker-compose up
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
Do not allow HTTP(S) traffic.  
Under `Disks` add the `persistent-db` as an additional disk and hit `Done`.
Under `Networking` set the hostname as `db.rctrials.internal`.

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
Install vim:
```
> apt-get update
> apt-get install vim iputils-ping
```
Edit hosts to connect to the db instance:
```
> vim /etc/hosts
```
add a line similar to:
```
[IP_OF_DB_INSTANCE]  db
```
test connection:
```
> ping db
```


#### Install LetsEncrypt
Connect to instance and enter the container:
```

```
Then execute the following:
```
> apt-get update
> apt-get install git
> cd /
> mkdir letsencrypt
> git clone https://github.com/letsencrypt/letsencrypt
> cd /letsencrypt
> ./letsencrypt-auto -d rctrials.interfacemaster.ca certonly
...
> service apache2 restart
```

# NTNUI Swimming web page

This repository contains source files for NTNUI Swimming web site. This repository contains a docker-compose file witch allows local development on any major OS.

## Installation
```bash
git clone git@github.com:pavelskipenes/org.ntnu.svommer.git
cd org.ntnu.svommer
scp -r username@server:path_to_translations translations/..
docker-compose up --build
chmod -R 777 {img,translations}
```
You should now be able to go to [https://127.0.0.1](https://127.0.0.1) and see the web page.
> Note that the text content is missing unless you download it from a live server. Here is an example on how to download the files:
> ```bash
> scp -r pavelgs@login.stud.ntnu.no:svommer/translations/ translations/..
> ``` 

## Starting and stopping local server

To start the server:
```bash
docker-compose up
# or
docker-compose up -d # to release the terminal
```

to stop the server:
```
docker-compose down
```

## Start over again
Some times you just say fuck it. And you want to restart the development. Here are the steps:
```bash
# stop the docker from running images
docker-compose down
# remove entire repository
cd ..
rm -rf org.ntnu.svommer
# remove all docker cache
docker system prune -a 
```
Then follow steps in Installation above

## Connecting to the containers
You have full access to the docker containers as if they were virtual machines. You can connect to the using `docker exec IMAGE_NAME /bin/bash` and navigate the file system. useful if you want to adjust the server settings or something like that. You can list all running images by running `docker ps`. Relevant image names are `web` and `mariadb`. The container is also running image `phpmyadmin` but there are no use cases where connecting to it would make sense.

## Check the log files
Some times things does not go as intended. Therefore checking logs are crucial. First of all you need to understand which program create what logs. `web` image runs php code and `mariadb` runs SQL queries. All logs are stored in `/var/log` and both images have access to that shared directory. Therefore you can connect to any of the images to check the logs. eg: `docker exec -it /bin/bash`.
|Path|What|
|----|----|
| `/var/log/php.log`                    | php errors, warnings and custom logs generated by eg `log(message)` |
| `/var/log/mysql/mariadb.log`          | SQL query log |
| `/var/log/mysql/mariadb_error.log`    | note, warning and error messages in mariadb |
| `/var/log/mysql/sql.log`              | I don't know whats here but it seems like there are some logs here too |
| `/var/log/mysql/sql_error.log`        | Seems like it contains query log |
> Log files are defined in `docker/mariadb/my.cfg`. This config should be updated so that log file names correspond with the actual content.

## How this project is built up
This project contains three four images:
- web
- mariadb
- phpmyadmin

### web image
web image is an custom Arch Linux image running php server (`Dockerimage` is in the directory). It hosts the actual code we develop. Why an Arch Linux image? I tried using Alpine Linux but I didn't manage to make it work just yet. Arch Linux was quite similar my system (Manjaro Linux) so I copied my custom settings files (in setup directory) and copied them to the image. Arch by design runs only the latest programs and doesn't change any of their configurations unlike say Ubuntu. That means this image does not replicate server configuration exactly and certain functions will not be available in production as they are in development. However that is a good thing since that means we can develop the web site  for a feature configuration and be ready for any changes and avoid migration hell like in the past. Honestly we could actually add one more Ubuntu image to the project and replicate current state of the production server. Something to consider at a later stage.

### mariadb
mariadb is an Ubuntu image running only the mariadb program.

### phpmyadmin
phpmyadmin is just a front end for the database. From there we can inspect and alter data in the mariadb database. I think it runs Debian Linux.

## Install server stuff without using docker
~Check the installation instructions in slab for local development.~
Check the docker-compose and Dockerfile and take inspiration on how to run it native on your system.

## Warning in regards to safety
When this docker-compose is running the website and it's database are accessible to anyone on your local network unless you have a firewall rule that blocks out that traffic. Make sure if you sit at a public place (NTNU, work ei everywhere except at home) that you enable a firewall that blocks incoming TCP connections to port (at least) 80 and 443. Database is not properly secured and thus personal data (if you for some reason have any) might get easily leaked.

## TODO

### Split web image
Currently web image are running the front end and the API. If we manage to split those two then we will be able to host the front end on any web page while preserving all out functions. For we can keep just the database and the API for it on org.ntnu.no and create a new front end on ntnui.no/svomming (or any other site we want)

TODO: Convert images to use Alpine Linux
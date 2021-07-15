# NTNUI Swimming web page

This repository contains source files for NTNUI Swimming web site. This repository contains a docker-compose file witch allows local development on any major OS.

## First time setup
> You need docker and docker-compose installed on your system first

For Unix-like operating systems:
```bash
mkdir -p ~/.config/docker/storage/ # create a directory for persistent storage
docker-compose up --build # Build the project
# Look for output like GENERATED ROOT PASSWORD: A&Vuj<N,({QwZ+x&FKGBe1@afTOr`;|_
```

1. Navigate to https://mysqladmin.it.ntnu.no/ and export the database to a file.
2. Navigate to http://127.0.0.1:42069 then the tab "User accounts" with username `root` and that was printed when running `docker-compose up`
3. Change permissions on `svommer_web` user. Give the user all access. (TODO: find the minimum permissions required)
4. Go to tab Import and import the file from step 1.

Now the website should be hosted on http://127.0.0.1 (with a working redirect to https) and https://127.0.0.1 with self signed certificate. You'll probably get a warning from web browser client about that this is not safe, but as any professional developers we will ignore that message and carry on by skipping that warning. Now you should have a fully working replica of the swimming web site that you can fuck with as much as you want without it having any consequences at all.

If you still get a connection error to database then there is probably an error with the ip-address in settings.json file. Update the ip-address in the default.json file given by this command: `docker exec mariadb /bin/hostname -I`. Then do a `docker-compose up --build --remove-orphans`. Why `default.json` and not `settings.json`? Because dockerfile doesn't get `settings.json` file and creates it's own from `default.json`.

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

## Tips

### Remove docker files and start over
If something is wrong with the development environment and you want to start over, do this:
```bash
docker-compose down
docker system prune -a
sudo rm -rf  ~/.config/docker/storage/* # doing this requires reconfiguring MariaDB
```
Now you should be able to restart the installation described above. Note that this will remove all other images you have cached on your computer.

### Connecting to the containers
You have full access to the docker containers as if they were virtual machines. You can connect to the using `docker exec IMAGE_NAME /bin/bash` and navigate the file system. useful if you want to adjust the server settings or something like that.

## How this project is built up
This project contains three four images:
- web
- mariadb
- phpmyadmin
- logger

### web image
web image is an custom Arch Linux image running php server (Dockerimage is in the directory). It hosts the actual code we develop. Why an Arch Linux image? I tried using Alpine Linux but I didn't manage to make it work just yet. Arch Linux was quite similar my system (Manjaro Linux) so I copied my custom settings files (in setup directory) and copied them to the image. Arch by design runs only the latest programs and doesn't change any of their configurations unlike say Ubuntu. That means this image does not replicate server configuration exactly and certain functions will not be available in production as they are in development. However that is a good thing since that means we can develop the web site  for a feature configuration and be ready for any changes and avoid migration hell like in the past. Honestly we could actually add one more Ubuntu image to the project and replicate current state of the production server. Something to consider at a later stage.

### mariadb
mariadb is an Ubuntu image running only the mariadb program. It stores it's database in a persistant storage in `~/.config/docker/storage/IMAGE_NAME`. This path is totally custom made and can be altered in `docker-compose.yml`. Path structure are different in Windows unlike Unix, so the path should be changed here *somehow* to make it work on Windows. Problem for a different day I guess. Or you can just remove that line but you loose persistent database during development.

Docker image is based on Arch Linux and not Ubuntu like production server. The difference are the version numbers on packages. Most noticeable are php version. In Arch it runs always the latest version however Ubuntu is usually holding it back. At the time of writing production server is running php 7 while the docker image is running php version 8.

### phpmyadmin
phpmyadmin is just a front end for the database. From there we can inspect and alter data in the mariadb database. I think it runs Debian Linux.

### logger
Honestly, this image is here just because I didn't manage to make web image print out php logs. web image saves logs to a file stored on a shared volume between web and this image. This image just prints out the contents of that log file.

## install server stuff without using docker
Check the installation instructions in slab for local development.

## Warning in regards to safety
When this docker-compose is running the website and it's database are accessible to anyone on your local network unless you have a firewall rule that blocks out that traffic. Make sure if you sit at a public place (NTNU, work ei everywhere except at home) that you enable a firewall that blocks incoming TCP connections to port (at least) 80 and 443. Database is not properly secured and thus personal data might get easily leaked.

## TODO

### finish the db setup
Database does not create it self. Fix that. Need a script that:
- Creates a user and sets permissions
- Creates db structure
- Fills the tables with fictional data

### Split web image
Currently web image are running the front end and the API. If we manage to split those two then we will be able to host the front end on any web page while preserving all out functions. For we can keep just the database and the API for it on org.ntnu.no and create a new front end on ntnui.no/svomming (or any other site we want)

TODO: Convert images to use Alpine Linux
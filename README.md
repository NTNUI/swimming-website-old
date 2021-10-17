# NTNUI Swimming web page
![image](https://user-images.githubusercontent.com/38912521/137648694-a6dc977e-5652-4da1-a54d-afaf97e26732.png)

This repository contains source files for NTNUI Swimming web site. This repository contains a docker-compose file witch allows local development on any major OS.

## Installation
Check the wiki for instructions.

## Usage
Check the wiki for instructions.

## Project overview
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
When this docker-compose is running the website and it's database are accessible to anyone on your local network unless you have a firewall rule that blocks out that traffic. Make sure if you sit at a public place (NTNU, work ei everywhere except at home) that you enable a firewall that blocks incoming TCP connections to port (at least) 80, 443 and 3306. Database is not properly secured and thus personal data might get easily leaked.

## Long term goals
- Finish API and move to other front ends
- Add sentralized logging server image and Xdebug to images
- Generate random user data for testing
- CI: linting and validating css, js, php etc...
- CD: add a callback hook that `git pull`s in the api
- Dark mode

## TODO

### finish the db setup
Database does not create it self. Fix that. Need a script that:
- Creates a user and sets permissions
- Creates db structure
- Fills the tables with fictional data

### Split web image
Currently web image are running the front end and the API. If we manage to split those two then we will be able to host the front end on any web page while preserving all out functions. For we can keep just the database and the API for it on org.ntnu.no and create a new front end on ntnui.no/svomming (or any other site we want)

### Move to Alpine Linux for images
Convert images to use Alpine Linux

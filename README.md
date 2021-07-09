# NTNUI Swimming web page

This repository contains source files for NTNUI Swimming web site. This repository contains a dockerfile witch allows local development on any OS major OS.

## Getting started

1. Build docker image
```
docker build -t ntnui-swimming
```

2. Run docker image
```
docker run -d -p 80:80 -p 443:443 ntnui-swimming
```
Now the website should be hosted on 127.0.0.1:80 and 127.0.0.1:443 with self signed certificate. You'll probably get a warning from web browser client about that. 


Stop all containers
```
docker stop $(docker ps -aq)
```

Check id of running images
```
docker ps
```

Check logs from container
```
docker logs <container-id>
```

## Missing parts
Docker image is based on arch linux and not ubuntu like production server. The difference are the version numbers on packages. Most noticeable are php version. In arch it runs always the latest version however ubuntu is usually holding it back. At the time of writing production server is running php 7.

## How this is created
Check the installation instructions in slab for local development.

## TODO:
- [highest] finish the db setup
- [medium] create a script for filling db with fictional data
- [medium] split images into front end image, back end image, db image and phpmyadmin image (some other db front ends could works as well tbh)
- [low] Update commands in slab for a more headless setup
- [low] Convert images to use Alpine linux
- [low] add instructions for installing stuff locally
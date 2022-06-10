# NTNUI Swimming web page
![image](https://user-images.githubusercontent.com/38912521/137648694-a6dc977e-5652-4da1-a54d-afaf97e26732.png)

This repository contains source files for NTNUI Swimming web site.

## Features
- Automatic membership approval
- Web store
- Membership management
- Mass license payment for members
- User managed content

## Installation
```bash
git clone git@github.com:NTNUI/org.ntnu.svommer.git
cd org.ntnu.svommer
cp .env.example .env # fill inn api keys in .env file
docker-compose up -d --build
chmod -R 777 img
chmod -R 777 translations
```
> Open .env file and fill inn your API test keys. If you don't have them you can fill out some random string but certain functionality will be limited (like e-commerce or captcha).
## Developing

### Default local testing credentials
The project uses `admin` as a username and `testing_password` as password for all accounts.
Those credentials are defined in .env file and in `docker/mariadb/databases/database.sql` file.
At this time those credentials are not in sync. 
1. update username and password in .env file
2. update username in databases.sql file
3. restart containers and log into [phpmyadmin](http://localhost:42069) with new username and old password.
4. Navigate to user accounts, find the new username and click on export
5. You should get a line similar to this:
```sql
GRANT ALL PRIVILEGES ON *.* TO `admin`@`%` IDENTIFIED BY PASSWORD 'HASHEDPASSWORDSTRING' WITH GRANT OPTION;
```
Replace correct line in `docker/mariadb/databases/database.sql`.

### Useful commands
```bash
# Start local development server
docker-compose up -d
# Stop the development server
docker-compose down
# Delete all docker containers and volumes (start over)
docker system prune -a
# Connect to the running container
docker exec -it <container name> <shell or command> # check docker-compose for container names. shell is /bin/ash in alpine linux and /bin/bash otherwise
# List running containers
docker ps
# Print php errors continuously 
docker exec -it apache tail -f ssl_error.log | perl -pe 's/[Ww]arning/\e[33m$&\e[0m/g; s/[Ee]rror/\e[31m$&\e[0m/g; s/[Ii]nfo/\e[32m$&\e[0m/g;'
# recursively find all images and print their file sizes
for file in $(find . -name "*.jpg"); do exiv2 $file 2>/dev/null | grep -a "Image size\|File name" | cut -d: -f2; done
```

## Commits
[Read this random commit convention guide](https://karma-runner.github.io/6.3/dev/git-commit-msg.html) and try following it. If there are issues following this convention, create an issue and we'll discuss it's weaknesses.

## Usage
phpmyadmin is available at [http://localhost:42069](http://localhost:42069).

Web site has an admin dashboard for managing members. It can be accessed at [https://localhost/admin](https://localhost/admin)

## Contributing

Here are some steps you should take to start contributing. They are not in any particular order but the more you know the better. 
- Linux
- git version control
- Docker 
- PHP [Learn PHP the right way](https://www.youtube.com/playlist?list=PLr3d3QYzkw2xabQRUpcZ_IBk9W50M9pe-)
- SQL
- Javascript

A tip here is to look into everything a bit and then diving deep into PHP. It is strongly recommended to go through the entire PHP series.

Once you're ready look for [good first issues](https://github.com/NTNUI/org.ntnu.svommer/issues?q=is%3Aopen+is%3Aissue+label%3A%22good+first+issue%22). Otherwise just Ctrl+F for "TODO" in the source code and see if there is anything you can try solving.

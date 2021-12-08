# NTNUI Swimming web page
![image](https://user-images.githubusercontent.com/38912521/137648694-a6dc977e-5652-4da1-a54d-afaf97e26732.png)

This repository contains source files for NTNUI Swimming web site. This repository contains a docker-compose file witch allows local development on any major OS.

## Installation
```bash
git clone git@github.com:pavelskipenes/org.ntnu.svommer.git
cd org.ntnu.svommer
scp -r username@server:path_to_translations translations/..
docker-compose up -d --build
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
## Phpmyadmin
phpmyadmin is available on [http://127.0.0.1:42069](http://127.0.0.1:42069)

## admin pages
to access admin pages go to [https://127.0.0.1/admin](https://127.0.0.1/admin).

**Credentials:**
| username | password         |
| -------- | ---------------- |
| admin    | testing_password |

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
### Getting logs from php
```
docker exec -it web /bin/bash
tail -f /var/log/php.log | perl -pe 's/[Ww]arning/\e[33m$&\e[0m/g; s/[Ee]rror/\e[31m$&\e[0m/g; s/[Ii]nfo/\e[32m$&\e[0m/g;'
```

## Check the log files
Some times things does not go as intended. Therefore checking logs are crucial. First of all you need to understand which program create what logs. `web` image runs php code and `mariadb` runs SQL queries. All logs are stored in `/var/log` and both images have access to that shared directory. Therefore you can connect to any of the images to check the logs. eg: `docker exec -it /bin/bash`.
| Path                               | What                                                                   |
| ---------------------------------- | ---------------------------------------------------------------------- |
| `/var/log/php.log`                 | php errors, warnings and custom logs generated by eg `log(message)`    |
| `/var/log/mysql/mariadb.log`       | SQL query log                                                          |
| `/var/log/mysql/mariadb_error.log` | note, warning and error messages in mariadb                            |
| `/var/log/mysql/sql.log`           | I don't know whats here but it seems like there are some logs here too |
| `/var/log/mysql/sql_error.log`     | Seems like it contains query log                                       |
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

## Project guidelines

### Content
Content is data that fills the web site. It's paragraphs, titles, images and in different languages. Content change while the source stays the same. All content should be stored in db and retrieved through API endpoints. Assets that cannot be stored in the db like images will be stored inside assets folder as a sort of temporary storage. When content is added / uploaded it gets a random hash name and gets stored in there. 
TODO: Create db for storing translations, add API endpoint and remove translations.php and translations dir.

### Source
Source is what is in this repository and should be kept separate from content as much as possible. 

### Common
- Class names starts with capital letter
- Only one class per file
- filename and class name should match.
- All filenames should be lowercase letters with exception of, by convention, Dockerfile
- Use strong typing whenever possible
- Exported functions needs to be documented with information about inputs, outputs and eventual side effects that is produced
- Try keeping side effects in functions as a minimum. Max one side effect per function / method.
- Use only english variable names and constants if possible
- Split between content and source. Content should be loaded by source and by dynamic while content should stay static
- Use object oriented programming whenever possible. In php it allows usage for exception handling which is preferred over return value checking because it reduces code size.
- Split pages into it's own style file, script file and php file
- Use snake_case for methods, function names and style classes
- keep all entry points small and clean
- Treat all warning as errors. If something is not working that is not critical crash the web server
- Describe same stuff equivalent everywhere. For instance use always variable name `phone` for storing phone number. Do not mix and match with other variants like `phone_number` for instance.

## js
- as much js code should be placed in js files in stead of php files
- base.js will provide functionality for all files page.js will provide functionality for specific page
- if some function is reusable put it inside a module or in base.js
- defer js loading if possible
- use modules if possible
- never use 'var' keyword. prefer 'const' over 'let'

### css
- base.css will apply styles for all pages
- theme.css will set all colors across the site as variables
- mobile.css will overwrite base rules if needed 
- page.css is used only to overwrite base.css and mobile.css styles
- prefer to use base.css over page.css whenever possible
- Never inline style in html. Use classes. 

### html
- reuse blocks across the project from templates.
- Use as little div indentations as possible.
- Do not set style class on something that does not need it.

### php
- Php return content only through API endpoints in json format.
- php pages will return html with it's corresponding script.js file and page.css style file if they exists.
- Prefer using class types as arguments if it makes sense over plain built in types.
- For API use RESTless principle if possible. If not try mimic them to best degree.

### Tests
Tests are not priority in this project at this time. Prefer spending time refactoring project into classes and use strong typing in stead. Use automated linters in GitHub actions 

## Classes in this project
#### Person
A Person is an abstract class that will derive classes Customer, User and Member
TODO: Create class

### User
User is someone who has an account and can authenticate themselves into /admin pages. They could be combined into a Member class but for now they're not.
TODO: create class

### Member
A Member is a Person that contains personal information required for registration.
TODO: create class

### Customer
A Customer is a Person that is performing a checkout in the store. Billing details are attached to that class.
> Note that a Customer is not attached in any way to a Member or wise versa. A Customer does not require to be a Member and these two data structures are never linked together.
TODO: Create class

### Store
Store is a helper class that wraps Customer, Product, Order and Checkout together. It provide helper functions and wrappers to connect to the Stripe API, and save persistent data to database.
TODO: Merge store.php and store_helper.php into store.php

### Product
A Product is something that is being sold at the store.
TODO: create class

### Order
An Order is a tuple of a Person and an Order. 
TODO: create class

### Checkout
The process of committing to fulfill an order
TODO: Create class

### DB
Database class is a wrapper for built in `\mysqli` class to wrap common calls together and to add exceptions on any unexpected result. This allows for more compact and error prone code overall in the project.

### Authenticator
A wrapper class for providing authentication services and  
TODO: merge access_control.php with authenticator.php

### API
Wrapper / helper class for API endpoints. Authenticate, log requests and return generic error messages on crashes.

### Settings
Load settings from settings.json file and apply them. Settings test also directory for read and write access on startup to test illegal configurations.
TODO: consider to switch from settings.json to settings.php and use a class in stead. That might be a better solution because all php configuration options can be set there. Right now they are taking up ~10 lines in index.php

### Log
Logging class that provide standardized way of logging stuff. Each log will provide the file name, file line a custom message and a backtrace in case of errors. These errors gets logged into a file defined in .htaccess file. There are three categories: Info, Warning and Error.

Info describe information about what is going on behind the scenes. For instance that a user has been registered or that a refund webhook has been received.

Warnings are minor bugs or potential bugs that should be fixed. For instance when some one requests a page that is not found on the server that results in an warning because we might have a dead link on the server somewhere.

Errors are crashes that stop the execution on the server and results with a client receiving an error message as a modal if the use web browser or as an json object if they use API. Errors get logged and require a developer to investigate and fix the issue.

## TODO

### Split web image
Currently web image are running the front end and the API. If we manage to split those two then we will be able to host the front end on any web page while preserving all out functions. For we can keep just the database and the API for it on org.ntnu.no and create a new front end on ntnui.no/svomming (or any other site we want)

### Refactoring
```js
// replace
element.classListAdd("hidden");
element.classListRemove("visible");
// with
element.classListReplace("hidden", "visible");
```
```php
// replace
include_once("file.php");
// with
require_once("file.php");
```

### Move to Alpine Linux for images
Convert images to use Alpine Linux

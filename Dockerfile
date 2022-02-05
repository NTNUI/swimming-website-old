FROM alpine

RUN cat /etc/apk/repositories 
RUN apk update
RUN apk add php composer openssl apache2 php7-apache2 php7-bz2 php7-common php7-pdo php7-gd php7-mysqli php7-zlib php7-curl shadow

#php-apache php-dblib composer neovim bat micro perl

WORKDIR /srv/http/

# Copy source over
COPY settings settings/
COPY index.php .

RUN mkdir sessions
RUN mkdir vendor
RUN mkdir translations
RUN mkdir -p img/store

RUN mkdir /var/lib/mariadb
RUN chmod -R 777 /var/lib/mariadb

COPY translations/ translations
COPY composer.json .
COPY composer.lock .
RUN composer install --no-plugins --no-scripts

RUN chmod -R 777 sessions
RUN chmod -R 777 vendor
RUN chmod -R 777 translations
RUN chmod -R 777 img

# Configure server
COPY docker /app
WORKDIR /app

# Configure SSL
RUN openssl genrsa -out server.key 1024
RUN openssl req -new -key server.key -out server.csr -config cert.ini
RUN openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
RUN mv server.{key,crt} /etc/httpd/conf/; rm server.csr

# Configure apache
RUN mkdir -p /etc/httpd/conf/
RUN cp httpd.conf /etc/httpd/conf/
RUN mkdir -p /etc/php/
RUN cp php.ini /etc/php/
RUN mkdir -p /etc/httpd/conf/extra/
RUN cp httpd-ssl.conf /etc/httpd/conf/extra/httpd-ssl.conf

# Configure project settings
RUN cp -f .htaccess /srv/http/
RUN cp -f docker.json /srv/http/settings/settings.json

# Add http user
RUN useradd http
RUN mkdir -p /var/www/logs/
RUN chmod -R 777 /var/www/logs/
RUN usermod -aG http http

WORKDIR /srv/http
# Run
EXPOSE 80 443

CMD ["/usr/sbin/httpd", "-DFOREGROUND"]
# CMD ["find", "-name", "mod_mpm_prefork.so"]
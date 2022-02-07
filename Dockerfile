FROM alpine

# Install required packages
RUN apk update
RUN apk upgrade
RUN apk add composer shadow openssl apache2 apache2-ssl neovim micro perl bat

# To use php version 8 uncomment commented line and vise versa
# RUN apk add php8 php8-apache2 php8-common php8-embed php8-session php8-json php8-mysqli
RUN apk add php7 php7-apache2 php7-common php7-embed php7-session php7-json php7-mysqli

# Configure self signed sertificate
WORKDIR /tmp
COPY docker/cert.ini .
RUN openssl genrsa -out server.key 1024
RUN openssl req -new -key server.key -out server.csr -config cert.ini
RUN openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
RUN mv server.key /etc/apache2/conf.d/
RUN mv server.crt /etc/apache2/conf.d/
RUN rm server.csr

# Use a custom apache server config which allows rewriting of configuration using .htaccess files
COPY docker/httpd.conf /etc/apache2/httpd.conf


# Copy over source files
WORKDIR /var/www/localhost/htdocs
RUN mkdir -p sessions translations img/store settings
COPY index.php .
COPY docker/.htaccess .
COPY composer.json .
COPY composer.lock .
COPY docker/docker.json settings/settings.json

# Modify permissions
## Read
RUN chmod -R 755 settings
RUN chmod -R 755 .htaccess
## Write
RUN chmod -R 775 translations
RUN chmod -R 775 sessions
RUN chmod -R 775 img/store
RUN chown -R apache:www-data translations
RUN chown -R apache:www-data sessions
RUN chown -R apache:www-data img/store

RUN chown apache:www-data .htaccess
RUN rm index.html
RUN composer install --no-plugins --no-scripts

# Logs are stored here. Faster access to logs when "docker exec -it web_php /bin/ash"
WORKDIR /var/log/apache2/

EXPOSE 80 443
CMD ["httpd", "-DFOREGROUND"]

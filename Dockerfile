FROM archlinux

RUN pacman -Syu --noconfirm mariadb php php-apache phpmyadmin mariadb mariadb-clients mariadb-libs php-dblib

# Copy files and set permission
COPY . /srv/http
RUN chown -R http:http /srv/http
RUN mkdir -p /usr/share/webapps/phpMyAdmin/config
RUN chown http:http /usr/share/webapps/phpMyAdmin/config
RUN chmod 750 /usr/share/webapps/phpMyAdmin
RUN cp srv/http/settings/httpd.conf /etc/httpd/conf/httpd.conf
RUN cp srv/http/settings/phpmyadmin.conf /etc/httpd/conf/extra/phpmyadmin.conf

# Configure SSL
RUN openssl genrsa -out server.key 1024
RUN openssl req -new -key server.key -out server.csr -config /srv/http/settings/cert.ini
RUN openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
RUN mv server.{key,crt} /etc/httpd/conf/

# Configure mysql and mariadb
# TODO: finish this
RUN mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql

# Configure php and phpmyadmin
RUN cp /srv/http/settings/php.ini /etc/php/php.ini

# Configure application 
RUN cp /srv/http/settings/default.json /srv/http/settings/settings.json

# Run
EXPOSE 80 443
CMD httpd && cd '/usr' ; /usr/bin/mysqld_safe --datadir='/var/lib/mysql'

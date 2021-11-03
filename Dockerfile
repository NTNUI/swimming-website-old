FROM archlinux

RUN pacman -Syu --noconfirm php php-apache php-dblib

WORKDIR /srv/http/

# Copy source over
COPY settings settings/
COPY index.php .
COPY vendor/ vendor
COPY translations/ translations
RUN mkdir sessions
RUN chmod 777 -R sessions
RUN chmod 777 -R vendor
RUN chmod 777 -R translations

# Configure server
COPY docker /app
WORKDIR /app

# Configure SSL
RUN openssl genrsa -out server.key 1024
RUN openssl req -new -key server.key -out server.csr -config cert.ini
RUN openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
RUN mv server.{key,crt} /etc/httpd/conf/; rm server.csr

# Configure apache
RUN cp httpd.conf /etc/httpd/conf/
RUN cp php.ini /etc/php/
RUN cp httpd-ssl.conf /etc/httpd/conf/extra/httpd-ssl.conf

# Configure project settings
RUN cp -f .htaccess /srv/http/
RUN cp -f docker.json /srv/http/settings/settings.json

# Run
EXPOSE 80 443
CMD httpd -D FOREGROUND

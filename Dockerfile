FROM archlinux

WORKDIR /app
COPY docker /app

RUN pacman -Syu --noconfirm php php-apache php-dblib vim tree

# Configure SSL
RUN openssl genrsa -out server.key 1024
RUN openssl req -new -key server.key -out server.csr -config cert.ini
RUN openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
RUN mv server.{key,crt} /etc/httpd/conf/; rm server.csr

RUN cp httpd.conf /etc/httpd/conf/
RUN cp php.ini /etc/php/
RUN cp httpd-ssl.conf /etc/httpd/conf/extra/httpd-ssl.conf

RUN chown -R http:http .

# Run
WORKDIR /srv/http
EXPOSE 80 443
CMD httpd -D FOREGROUND

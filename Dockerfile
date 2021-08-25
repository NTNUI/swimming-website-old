FROM archlinux

WORKDIR /app
COPY docker /app

RUN pacman -Syu --noconfirm php php-apache php-dblib neovim tree xdebug lsd

# Configure SSL
RUN openssl genrsa -out server.key 1024
RUN openssl req -new -key server.key -out server.csr -config cert.ini
RUN openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
RUN mv server.{key,crt} /etc/httpd/conf/; rm server.csr

RUN cp httpd.conf /etc/httpd/conf/
RUN cp php.ini /etc/php/
RUN cp httpd-ssl.conf /etc/httpd/conf/extra/httpd-ssl.conf

# fix minimal permissions
RUN chmod -R 00750 {.,.*}
RUN chmod -R 00750 {css,img,js,library,private,public,settings,vendor,index.php}
RUN chmod -R 755 .htacess
RUN chmod -R 1755 {sessions,translations,img/store}

# Run
WORKDIR /srv/http
EXPOSE 80 443
CMD httpd -D FOREGROUND

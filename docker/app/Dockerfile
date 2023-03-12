FROM alpine

# Install required packages
RUN apk update
RUN apk upgrade
RUN apk add \
    openssl \
    apache2 \
    apache2-ssl \
    php81 \
    php81-apache2 \
    php81-common \
    php81-embed \
    php81-session \
    php81-json \
    php81-mysqli \
    php81-iconv \
    php81-curl \
    php81-mbstring \
    neovim \
    micro \
    perl \
    bat


# Copy over configs and application data
WORKDIR /var/www/localhost/htdocs

# Generate self signed sertificate
RUN openssl req -new -newkey rsa:2048 -nodes -keyout /etc/ssl/apache2/server.key -out /etc/ssl/apache2/server.csr -subj "/CN=localhost"
RUN openssl x509 -req -days 365 -in /etc/ssl/apache2/server.csr -signkey /etc/ssl/apache2/server.key -out /etc/ssl/apache2/server.crt -extfile <(echo "basicConstraints = CA:FALSE")

COPY img ./img
COPY translations ./translations

RUN rm index.html && \
    mkdir sessions log
# 100:101 is apache user
RUN chown -R 100:101 sessions translations img log
RUN chmod -R 775 sessions translations img log

# Logs are stored here. Faster access to logs when "docker exec -it app /bin/ash"
WORKDIR /var/log/apache2/

# Convinient shortcuts
RUN ln -s /var/www/localhost/htdocs /var/log/apache2/htdocs
RUN ln -s /var/log/apache2/ /var/www/localhost/htdocs/logs

EXPOSE 80 443
CMD ["httpd", "-DFOREGROUND"]

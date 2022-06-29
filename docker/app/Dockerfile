FROM alpine

# Install required packages
RUN apk update
RUN apk upgrade
RUN apk add \
    openssl \
    apache2 \
    apache2-ssl \
    php8 \
    php8-apache2 \
    php8-common \
    php8-embed \
    php8-session \
    php8-json \
    php8-mysqli \
    php8-iconv \
    php8-curl \
    php8-mbstring \
    neovim \
    micro \
    perl \
    bat

# Copy over configs and application data
WORKDIR /var/www/localhost/htdocs

COPY img ./img
COPY translations ./translations

RUN rm index.html && \
    mkdir sessions
# 100:101 is apache user
RUN chown -R 100:101 sessions translations img
RUN chmod -R 775 sessions translations img

# Logs are stored here. Faster access to logs when "docker exec -it app /bin/ash"
WORKDIR /var/log/apache2/

# Convinient shortcuts
RUN ln -s /var/www/localhost/htdocs /var/log/apache2/htdocs
RUN ln -s /var/log/apache2/ /var/www/localhost/htdocs/logs

EXPOSE 80 443
CMD ["httpd", "-DFOREGROUND"]
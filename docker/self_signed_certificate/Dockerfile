FROM alpine

RUN apk update
RUN apk upgrade
RUN apk add openssl

WORKDIR /app

CMD openssl genrsa -out /out/server.key 1024 && \
    openssl req -new -key /out/server.key -out /out/server.csr -config cert.ini && \
    openssl x509 -req -days 365 -in /out/server.csr -signkey /out/server.key -out /out/server.crt
FROM openswoole/swoole:php8.2-alpine

RUN apk update
RUN apk add libzip-dev libxml2 libxml2-dev git
RUN docker-php-ext-install pdo pdo_mysql zip xml
WORKDIR /var/www/

ENTRYPOINT ["sh", "start.sh"]

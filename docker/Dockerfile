FROM phpswoole/swoole:php8.2-alpine

RUN docker-php-ext-install pdo pdo_mysql
RUN apk add --update bind-tools
WORKDIR /var/www/

ENTRYPOINT ["sh", "start.sh"]
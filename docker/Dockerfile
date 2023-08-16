FROM openswoole/swoole:php8.2-alpine

RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www/

ENTRYPOINT ["sh", "start.sh"]
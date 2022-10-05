FROM php:8.1-fpm as base

#Зависимости всякие разные
RUN apt-get update && apt-get install -y git zip unzip wget ntp gnupg python2 gosu curl ca-certificates libcap2-bin libpng-dev libcurl4-openssl-dev libonig-dev

#Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN docker-php-ext-install -j$(nproc) pdo_mysql mysqli

RUN apt-get clean
RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

#Подготовка файлов ядра
WORKDIR /var/www/html

FROM base as production
COPY ./ /var/www/html
RUN composer install --no-dev --optimize-autoloader

#Далее настройка образа для Nginx
FROM nginx:latest as nginx_production

#Включаем конфиг в default.conf
COPY ./docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf
#Копируем всё, что есть. Статика перенесётся, а PHP файлы всё равно обрабатывает FastCGI
COPY --from=production /var/www/html /var/www/html
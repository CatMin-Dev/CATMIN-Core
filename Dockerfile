FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring intl zip gd opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/catmin.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/storage /var/www/html/cache /var/www/html/logs /var/www/html/sessions /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]

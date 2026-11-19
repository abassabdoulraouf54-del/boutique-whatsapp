FROM php:8.2-apache
RUN apt-get update && apt-get install -y git unzip libicu-dev libzip-dev \
 && docker-php-ext-install intl pdo pdo_mysql zip opcache \
 && a2enmod rewrite
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN chown -R www-data:www-data var public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
EXPOSE 80

FROM php:8.4-apache

RUN apt-get update && apt-get install -y git unzip libicu-dev libzip-dev libonig-dev libxml2-dev libsqlite3-dev \
    && docker-php-ext-install intl pdo pdo_mysql pdo_sqlite zip opcache \
    && a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN mkdir -p var && php bin/console doctrine:database:create --if-not-exists --env=prod || true
RUN php bin/console doctrine:migrations:migrate --no-interaction --env=prod || php bin/console doctrine:schema:update --force --env=prod || true
RUN chown -R www-data:www-data var public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

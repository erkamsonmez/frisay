FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libonig-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli mbstring gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html
WORKDIR /var/www/html
COPY . /var/www/html/

# Apache must write installer configuration and generated assets.
RUN mkdir -p cache img/products img/returns img/invoices logs backups storage/digital \
    && chown -R www-data:www-data config cache img logs backups storage \
    && chmod -R u+rwX,g+rwX config cache img logs backups storage

EXPOSE 80
CMD ["apache2-foreground"]

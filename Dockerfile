FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libonig-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli mbstring gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . /var/www/html/
COPY docker/frisay-entrypoint.sh /usr/local/bin/frisay-entrypoint
RUN chmod +x /usr/local/bin/frisay-entrypoint \
    && mkdir -p cache img/products img/returns img/invoices logs backups storage/digital /var/frisay-data \
    && chown -R www-data:www-data config cache img logs backups storage /var/frisay-data \
    && chmod -R u+rwX,g+rwX config cache img logs backups storage
EXPOSE 80
CMD ["/usr/local/bin/frisay-entrypoint"]

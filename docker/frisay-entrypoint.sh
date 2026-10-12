#!/bin/sh
set -eu

mkdir -p /var/frisay-data
chown www-data:www-data /var/frisay-data
for name in env.php installed.lock; do
  if [ -f "/var/www/html/config/$name" ] && [ ! -L "/var/www/html/config/$name" ]; then
    cp "/var/www/html/config/$name" "/var/frisay-data/$name"
  fi
  rm -f "/var/www/html/config/$name"
  ln -s "/var/frisay-data/$name" "/var/www/html/config/$name"
done
chown -R www-data:www-data /var/frisay-data
exec apache2-foreground

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
# Persist storefront appearance and uploaded branding across Docker redeploys.
# Existing values always win over defaults shipped in the new image.
theme_dir="/var/www/html/templates/cnc-industrial/css"
state_dir="/var/frisay-data/themes/cnc-industrial"
if [ -d "$theme_dir" ]; then
  mkdir -p "$state_dir"
  for name in colors.css custom.css; do
    source="$theme_dir/$name"
    target="$state_dir/$name"
    if [ ! -f "$target" ] && [ -f "$source" ]; then
      cp "$source" "$target"
    fi
    rm -f "$source"
    ln -s "$target" "$source"
  done
fi

brand_source="/var/www/html/img/branding"
brand_target="/var/frisay-data/branding"
mkdir -p "$brand_target"
if [ -d "$brand_source" ] && [ ! -L "$brand_source" ]; then
  cp -a "$brand_source/." "$brand_target/"
  rm -rf "$brand_source"
fi
if [ ! -L "$brand_source" ]; then
  ln -s "$brand_target" "$brand_source"
fi

chown -R www-data:www-data /var/frisay-data
exec apache2-foreground

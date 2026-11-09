#!/bin/sh
# Bind Apache to $PORT (injected by Railway, Render, Fly, etc.) and default to
# 80 when nothing is set, so `docker run -p 8080:80` still works.
set -e

PORT="${PORT:-80}"

# Keep exactly one Apache MPM enabled.
# ------------------------------------------------------------------------------
# php:8.2-apache enables mpm_prefork, but an image can end up with mpm_event
# left enabled alongside it. Apache then refuses to start with:
#     AH00534: apache2: Configuration error: More than one MPM loaded.
# which shows up as a crash loop with no other explanation in the logs.
# Normalising here is idempotent and cheap, and keeps the container startable
# no matter what the base image did at build time.
for mpm in mpm_event mpm_worker; do
    a2dismod "$mpm" >/dev/null 2>&1 || true
done
a2enmod mpm_prefork >/dev/null 2>&1 || true

sed -ri "s/^Listen 80\$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "nuqtah-demo: Apache listening on port ${PORT}"
echo "nuqtah-demo: MPMs enabled: $(ls /etc/apache2/mods-enabled/ 2>/dev/null | grep '^mpm_' | tr '\n' ' ')"
exec apache2-foreground

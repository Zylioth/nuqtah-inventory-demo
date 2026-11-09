#!/bin/sh
# Bind Apache to $PORT (injected by Railway, Render, Fly, etc.) and default to
# 80 when nothing is set, so `docker run -p 8080:80` still works.
set -e

PORT="${PORT:-80}"

sed -ri "s/^Listen 80\$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "nuqtah-demo: Apache listening on port ${PORT}"
exec apache2-foreground

FROM php:8.2-apache

# The application uses PDO with prepared statements throughout; that is the
# only extension it needs beyond the base image.
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY . /var/www/html/

# Apache has to listen on the port the platform assigns. The entrypoint
# rewrites it at startup so the same image runs locally on :80.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

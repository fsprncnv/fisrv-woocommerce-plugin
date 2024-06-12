# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM php:apache-bookworm

# ---------------------------------------------------
# Self-Signed Certificate Generation
# ---------------------------------------------------
RUN apt-get update
RUN apt-get install -y unzip ssl-cert
RUN make-ssl-cert generate-default-snakeoil --force-overwrite
RUN usermod -a -G ssl-cert www-data

# ---------------------------------------------------
# Apache Configuration
# ---------------------------------------------------
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-enable mysqli
RUN a2enmod rewrite
RUN a2enmod ssl
RUN a2enmod vhost_alias
RUN a2ensite default-ssl

# ---------------------------------------------------
# Install Wordpress CLI
# ---------------------------------------------------
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

WORKDIR /var/www/html/
RUN wp core download --allow-root --path=. --locale=en_US
RUN wp config create --allow-root --skip-check --dbhost=fisrv-woocommerce-db --dbname=wordpress --dbuser=wordpress --dbpass=wordpress

# ---------------------------------------------------
# Copy Plugins
# ---------------------------------------------------
WORKDIR /var/www/html/wp-content/plugins/
ADD https://downloads.wordpress.org/plugin/woocommerce.8.9.3.zip woocommerce.zip
RUN unzip woocommerce.zip
RUN rm -f woocommerce.zip

ADD *.zip fiserv/plugin.zip
RUN unzip fiserv/plugin.zip
RUN rm -f fiserv/plugin.zip

RUN ls -al

RUN chown -R www-data:www-data /var/www

# ---------------------------------------------------
# PHP Configurations
# ---------------------------------------------------
WORKDIR /usr/local/etc/php
RUN cp php.ini-production php.ini
RUN sed -i 's/;zend_extension=opcache/zend_extension=opcache/g' php.ini
RUN sed -i 's/;opcache.enable=1/opcache.enable=1/g' php.ini
RUN sed -i 's/expose_php = On/expose_php = Off/g' php.ini

USER www-data

EXPOSE 443

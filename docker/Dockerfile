# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM php:apache-bookworm

# ---------------------------------------------------
# Self-signed Certificate Generation
# ---------------------------------------------------
RUN openssl req -new -newkey rsa:4096 -days 3650 -nodes -x509 -subj "/C=GB/ST=London/L=London/O=Global Security/OU=IT Department/CN=example.com" -keyout ./ssl.key -out ./ssl.crt
RUN ls -al
RUN ls -al /etc/ssl/certs/
RUN mv ./ssl.crt /etc/ssl/certs/ssl.crt
RUN mv ./ssl.key /etc/ssl/certs/ssl.key
RUN ls -al /etc/ssl/certs/

# ---------------------------------------------------
# Install Wordpress CLI
# ---------------------------------------------------
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp
RUN ls -al /usr/local/bin/

#USER www-data
RUN ls -al /usr/local/bin/
WORKDIR /var/www/html/

RUN wp --info
RUN wp core download --allow-root --path=. --locale=en_US
#RUN wp config create --allow-root --dbname=fiwoodb --dbuser=wordpress --prompt=Test1234
#RUN wp db create --allow-root
#RUN wp core install --allow-root --url=wpclidemo.dev --title="WP-CLI" --admin_user=wpcli --admin_password=wpcli --admin_email=info@wp-cli.org

# ---------------------------------------------------
# Copy Plugins
# ---------------------------------------------------
ADD *.tar.gz ./wp-content/plugins/custom-plugin/
RUN ls ./wp-content/plugins/custom-plugin/

RUN chown -R www-data:www-data /var/www
RUN ls -al /var/www
RUN ls -al .

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-enable mysqli
RUN a2enmod rewrite
RUN a2enmod ssl
RUN a2enmod vhost_alias
RUN a2ensite default-ssl

RUN ls -al /etc/apache2/sites-enabled/
RUN cat /etc/apache2/sites-enabled/sites-available/default-ssl.conf

# ---------------------------------------------------
# PHP Configurations
# ---------------------------------------------------
WORKDIR /usr/local/etc/php
RUN cp php.ini-production php.ini
RUN sed -i 's/;zend_extension=opcache/zend_extension=opcache/g' php.ini
RUN sed -i 's/;opcache.enable=1/opcache.enable=1/g' php.ini
RUN sed -i 's/expose_php = On/expose_php = Off/g' php.ini

USER www-data

EXPOSE 80
EXPOSE 443

# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM php:apache-bookworm
ARG CI_COMMIT_SHA
ENV CI_COMMIT_SHA $CI_COMMIT_SHA

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
WORKDIR /etc/apache2/conf-enabled
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-enable mysqli
RUN a2enmod headers rewrite ssl vhost_alias
RUN a2ensite default-ssl
RUN sed -i 's/ServerSignature On/ServerSignature Off/g' security.conf
RUN sed -i 's/ServerTokens OS/ServerTokens Prod/g' security.conf
RUN sed -i 's/#Header set X-Content-Type-Options: "nosniff"/Header always set X-Content-Type-Options: "nosniff"/g' security.conf
RUN echo 'Header always set X-Frame-Options: "sameorigin"' >> security.conf
RUN echo 'Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"' >> security.conf
RUN echo 'Header always set X-XSS-Protection "1; mode=block"' >> security.conf
RUN echo 'Header always set X-Permitted-Cross-Domain-Policies "none"' >> security.conf
RUN echo 'Header always set Referrer-Policy "no-referrer-when-downgrade"' >> security.conf
#RUN echo 'Header always set Content-Security-Policy "default-src *; style-src 'self' 'unsafe-inline' 'unsafe-hashes'; script-src 'self' 'unsafe-inline' 'unsafe-hashes'; script-src-elem 'self' *.mherrm.de"' >> security.conf
RUN echo 'Header always set Permissions-Policy "geolocation=(),midi=(),sync-xhr=(),microphone=(),camera=(),magnetometer=(),gyroscope=(),fullscreen=(self),payment=()"' >> security.conf
RUN echo 'Header always set X-Commit: "${CI_COMMIT_SHA}"' >> security.conf
RUN echo ${CI_COMMIT_SHA}

# ---------------------------------------------------
# PHP Configurations
# ---------------------------------------------------
WORKDIR /usr/local/etc/php
RUN cp php.ini-production php.ini
RUN sed -i 's/;zend_extension=opcache/zend_extension=opcache/g' php.ini
RUN sed -i 's/;opcache.enable=1/opcache.enable=1/g' php.ini
RUN sed -i 's/expose_php = On/expose_php = Off/g' php.ini
RUN sed -i 's/memory_limit = 128M/memory_limit = 256M/g' php.ini

# ---------------------------------------------------
# Install Wordpress CLI
# ---------------------------------------------------
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

WORKDIR /var/www/html/
RUN wp core download --allow-root --path=. --locale=en_US
RUN wp config create --allow-root --skip-check --dbhost=fisv-woocommerce-db --dbname=wordpress --dbuser=wordpress --dbpass=wordpress

# ---------------------------------------------------
# Copy Plugins
# ---------------------------------------------------
WORKDIR /var/www/html/wp-content/plugins/
ADD https://downloads.wordpress.org/plugin/woocommerce.8.9.3.zip woocommerce.zip
RUN unzip -qq woocommerce.zip
RUN rm -f woocommerce.zip

ADD *.zip plugin.zip
RUN unzip -qq plugin.zip -d fiserv/
RUN rm -f plugin.zip

RUN chown -R www-data:www-data /var/www

USER www-data

EXPOSE 443

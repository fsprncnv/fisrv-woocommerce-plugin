# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/docker/apache/apache:latest AS main

# ---------------------------------------------------
# Install Wordpress CLI
# ---------------------------------------------------
WORKDIR /var/www/html/
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar wp-cli.phar
RUN chmod +x wp-cli.phar
RUN /var/www/html/wp-cli.phar core download --allow-root --path=. --locale=en_US
RUN /var/www/html/wp-cli.phar config create --allow-root --skip-check --dbhost=fisv-woocommerce-db --dbname=wordpress --dbuser=wordpress --dbpass=wordpress

# ---------------------------------------------------
# Copy Plugins
# ---------------------------------------------------
WORKDIR /var/www/html/wp-content/plugins/
ADD *.zip plugin.zip
ADD https://downloads.wordpress.org/plugin/woocommerce.9.8.5.zip woocommerce.zip
RUN unzip -qq woocommerce.zip &&\
    rm -f woocommerce.zip &&\
    unzip -qq plugin.zip -d fiserv/ &&\
    rm -f plugin.zip &&\
    chown -R www-data:www-data /var/www

USER www-data

EXPOSE 443

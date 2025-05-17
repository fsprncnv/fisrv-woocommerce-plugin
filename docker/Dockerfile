# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM registry.gitlab.mherrm.de/default/docker/apache:latest

# ---------------------------------------------------
# Install Wordpress CLI
# ---------------------------------------------------
WORKDIR /var/www/html/
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar wp-cli.phar
RUN chmod +x wp-cli.phar
RUN wp-cli.phar core download --allow-root --path=. --locale=en_US
RUN wp-cli.phar config create --allow-root --skip-check --dbhost=fisv-woocommerce-db --dbname=wordpress --dbuser=wordpress --dbpass=wordpress

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

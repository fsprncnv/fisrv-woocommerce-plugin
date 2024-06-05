# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM wordpress:php8.2-apache

# ---------------------------------------------------
# Install Wordpress CLI
# ---------------------------------------------------
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

WORKDIR /var/www/html/
RUN wp --info
RUN wp core download --allow-root --path=. --locale=en_US
RUN wp config create --dbname=database --dbuser=wordpress --prompt=wordpress
RUN wp db create
RUN wp core install --url=wpclidemo.dev --title="WP-CLI" --admin_user=wpcli --admin_password=wpcli --admin_email=info@wp-cli.org

# ---------------------------------------------------
# Copy Plugins
# ---------------------------------------------------
WORKDIR /usr/src/wordpress
ADD *.tar.gz ./wp-content/plugins/custom-plugin/
#RUN tar -xzf *.tar.gz
#RUN rm *.tar.gz
RUN ls ./wp-content/plugins/custom-plugin/

EXPOSE 80

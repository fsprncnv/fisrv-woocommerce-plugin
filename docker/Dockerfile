# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM php:apache-bookworm

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
#RUN wp config create --allow-root --dbname=database --dbuser=wordpress --prompt=wordpress
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

USER www-data

EXPOSE 80

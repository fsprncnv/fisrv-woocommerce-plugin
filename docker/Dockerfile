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

RUN wp --info
RUN wp core download --path=/var/www/html/ --locale=en_EN

# Copy default index.php file
#COPY default/index.php /var/www/html/

# Copy static content from WordPress build stage
#COPY --from=build --chown=www-data:www-data /var/www/html/wp-content /var/www/html/wp-content


# ---------------------------------------------------
# Copy Plugins
# ---------------------------------------------------
WORKDIR /usr/src/wordpress
ADD *.tar.gz ./wp-content/plugins/custom-plugin/
#RUN tar -xzf *.tar.gz
#RUN rm *.tar.gz
RUN ls ./wp-content/plugins/custom-plugin/

RUN ls /var/www/html
RUN ls /usr/src/wordpress

EXPOSE 80

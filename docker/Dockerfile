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

# ---------------------------------------------------
# Copy Plugins
# ---------------------------------------------------
WORKDIR /usr/src/wordpress
ADD *.tar.gz ./wp-content/plugins/custom-plugin/
#RUN tar -xzf *.tar.gz
#RUN rm *.tar.gz
RUN ls ./wp-content/plugins/custom-plugin/

RUN ls /var/www/html

EXPOSE 80

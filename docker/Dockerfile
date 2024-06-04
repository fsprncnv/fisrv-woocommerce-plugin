# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM wordpress:php8.2-apache

# ---------------------------------------------------
# Install Weatherstation Service
# ---------------------------------------------------

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

RUN wp --info

EXPOSE 80

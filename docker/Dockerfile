# ---------------------------------------------------
# Global Arguments
# ---------------------------------------------------
FROM php:apache-bookworm

# ---------------------------------------------------
# Copy Files
# ---------------------------------------------------
WORKDIR /var/www/html
COPY *.gz .
RUN tar -xzf *.gz --strip-components=1
RUN rm *.gz
RUN chown -R www-data:www-data /var/www

# ---------------------------------------------------
# Apache Configurations
# ---------------------------------------------------
WORKDIR /etc/apache2/conf-enabled
RUN a2enmod headers rewrite
RUN sed -i 's/ServerSignature On/ServerSignature Off/g' security.conf
RUN sed -i 's/ServerTokens OS/ServerTokens Prod/g' security.conf
RUN sed -i 's/#Header set X-Content-Type-Options: "nosniff"/Header always set X-Content-Type-Options: "nosniff"/g' security.conf
RUN sed -i 's/#Header set X-Frame-Options: "sameorigin"/Header always set X-Frame-Options: "sameorigin"/g' security.conf
RUN echo 'Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"' >> security.conf
RUN echo 'Header always set X-XSS-Protection "1; mode=block"' >> security.conf
RUN echo 'Header always set X-Permitted-Cross-Domain-Policies "none"' >> security.conf
RUN echo 'Header always set Referrer-Policy "no-referrer-when-downgrade"' >> security.conf
#RUN echo 'Header always set Content-Security-Policy "default-src 'https:'; script-src 'unsafe-inline'"' >> security.conf
RUN cat security.conf

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

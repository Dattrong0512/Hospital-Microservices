FROM php:8.1-apache

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html/UDPT-QLBN

COPY . /var/www/html/UDPT-QLBN/

RUN chown -R www-data:www-data /var/www/html/UDPT-QLBN

RUN a2enmod rewrite

RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
FROM php:8.2-apache

# Activar mod_rewrite
RUN a2enmod rewrite

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copiar todo el proyecto
COPY . /var/www/html/

# Apache debe apuntar a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri \
  -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf \
  /etc/apache2/apache2.conf

WORKDIR /var/www/html
EXPOSE 80

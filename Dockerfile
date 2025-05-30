FROM php:8.1-apache

# Active mod_rewrite pour Apache (si besoin)
RUN a2enmod rewrite

# Installe les extensions nécessaires (mysqli pour MySQL)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copie tes fichiers PHP dans /var/www/html
COPY public/ /var/www/html/

# Donne les bons droits
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

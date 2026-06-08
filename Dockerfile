FROM php:8.2-apache

# Dependencias do sistema para compilar extensoes PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensoes PostgreSQL
RUN docker-php-ext-install pdo_pgsql pgsql

# Habilita mod_rewrite
RUN a2enmod rewrite

# Apache config
COPY ./frontend/apache.conf /etc/apache2/sites-available/000-default.conf

# Copia o codigo
COPY ./.htaccess /var/www/html/.htaccess
COPY ./index.php /var/www/html/index.php
COPY ./backend /var/www/html/backend
COPY ./frontend /var/www/html/frontend

# Node.js e npm para build do TailwindCSS
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm --prefix /var/www/html/frontend install \
    && npm --prefix /var/www/html/frontend run build

# Permissoes
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

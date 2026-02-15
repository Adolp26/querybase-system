FROM php:8.3-fpm

ARG user=querybase
ARG uid=1000

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

WORKDIR /var/www

COPY --chown=$user:$user . .

RUN composer install --no-interaction --no-dev --optimize-autoloader

RUN chown -R $user:$user /var/www/storage /var/www/bootstrap/cache

COPY --chown=$user:$user docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER $user

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
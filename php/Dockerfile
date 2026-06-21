FROM php:8.3-fpm

ENV PS1="\u@\h:\w\\$ "
ENV TZ="Asia/Jakarta"
ENV COMPOSER_MEMORY_LIMIT='-1'
ENV DEBIAN_FRONTEND=noninteractive

# ──────────────────────────────────────────────────────────
# System dependencies
#
# CATATAN PERBAIKAN:
#   '--force-yes' sudah DIHAPUS dari apt sejak Debian Stretch.
#   Jika tetap dipakai, build akan GAGAL dengan error:
#   "E: Command line option --force-yes is deprecated..."
#   Diganti dengan '-y' saja + '--no-install-recommends'.
# ──────────────────────────────────────────────────────────
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libmemcached-dev \
        libmcrypt-dev \
        libreadline-dev \
        libgmp-dev \
        libzip-dev \
        libz-dev \
        libpq-dev \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
        libssl-dev \
        libxml2-dev \
        libonig-dev \
        openssh-server \
        libmagickwand-dev \
        git \
        unzip \
        cron \
        nano \
        curl \
        ca-certificates \
        gnupg \
    && rm -rf /var/lib/apt/lists/*

# ──────────────────────────────────────────────────────────
# Node.js 20 LTS (dipisah dari apt, repo debian default sudah usang)
# ──────────────────────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y --no-install-recommends nodejs && \
    rm -rf /var/lib/apt/lists/*

# ──────────────────────────────────────────────────────────
# PHP Extensions bawaan
# ──────────────────────────────────────────────────────────
RUN docker-php-ext-install soap
RUN docker-php-ext-install exif
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install intl
RUN docker-php-ext-install gmp
RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install opcache

#####################################
# PHPRedis & MongoDB
#####################################
RUN pecl install redis && docker-php-ext-enable redis
RUN pecl install mongodb && docker-php-ext-enable mongodb

#####################################
# Imagick
#####################################
RUN pecl install imagick && \
    docker-php-ext-enable imagick

#####################################
# GD
#
# CATATAN PERBAIKAN:
#   Dockerfile asli memanggil docker-php-ext-install gd DUA KALI
#   (sebelum dan sesudah configure) -> boros waktu build & rawan
#   konflik konfigurasi. Disederhanakan jadi 1x configure + 1x install.
#####################################
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

#####################################
# xDebug
#
# CATATAN PERBAIKAN:
#   'pecl install xdebug' tanpa versi akan mengambil versi terbaru
#   yang bisa saja TIDAK kompatibel dengan PHP 8.3. Dipin ke versi
#   yang sudah teruji stabil untuk PHP 8.3.
#####################################
RUN pecl install xdebug-3.3.2

#####################################
# PHP Memcached
#####################################
RUN pecl install memcached && docker-php-ext-enable memcached

#####################################
# Composer
#####################################
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV PATH="${PATH}:/var/www/html/vendor/bin"

#####################################
# Laravel Schedule Cron Job
#####################################
RUN echo "* * * * * www-data /usr/local/bin/php /var/www/html/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel-scheduler && \
    chmod 0644 /etc/cron.d/laravel-scheduler && \
    crontab /etc/cron.d/laravel-scheduler

#--------------------------------------------------------------------------
# Final Touch
#--------------------------------------------------------------------------
COPY ./local.ini /usr/local/etc/php/conf.d/local.ini

#####################################
# Aliases
#####################################
RUN printf '#!/bin/bash\n/usr/local/bin/php /var/www/html/vendor/bin/dep "$@"\n' > /usr/bin/dep && chmod +x /usr/bin/dep
RUN printf '#!/bin/bash\n/usr/local/bin/php /var/www/html/artisan "$@"\n' > /usr/bin/art && chmod +x /usr/bin/art
RUN printf '#!/bin/bash\n/usr/local/bin/php /var/www/html/artisan migrate "$@"\n' > /usr/bin/migrate && chmod +x /usr/bin/migrate
RUN printf '#!/bin/bash\n/usr/local/bin/php /var/www/html/artisan migrate:fresh --seed\n' > /usr/bin/fresh && chmod +x /usr/bin/fresh
RUN printf '#!/bin/bash\n/usr/local/bin/php /var/www/html/artisan config:clear\n/var/www/html/vendor/bin/pest --colors=always "$@"\n' > /usr/bin/t && chmod +x /usr/bin/t

WORKDIR /var/www/html

COPY ./docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 9000
CMD ["php-fpm"]
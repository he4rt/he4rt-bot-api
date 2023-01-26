FROM php:8.1-fpm
WORKDIR /var/www
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions pdo_mysql zip exif pcntl gd redis dom

RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
    nginx

RUN apt-get install -y supervisor
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www
COPY . /var/www
COPY --chown=www:www . /var/www
RUN chmod -R 755 /var/www/storage
RUN cp docker/php.ini /usr/local/etc/php/conf.d/app.ini

RUN mkdir /var/log/php
RUN touch /var/log/php/errors.log && chmod +x /var/log/php/errors.log
COPY --from=composer /usr/bin/composer /usr/bin/composer

EXPOSE 9000
CMD ["php-fpm"]

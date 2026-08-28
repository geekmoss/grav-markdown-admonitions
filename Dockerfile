FROM php:8.3-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd intl zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /opt/grav

# The plugin's tests depend on Grav Markdown classes, which are intentionally
# supplied by the host Grav installation rather than this plugin's composer.json.
RUN composer create-project --no-interaction --no-progress --prefer-dist getgrav/grav:^2.0 .

WORKDIR /opt/grav/user/plugins/markdown-admonitions
COPY composer.json ./
RUN composer install --no-interaction --no-progress --prefer-dist

COPY . ./

# Preload Grav's autoloader as the plugin test bootstrap first registers the
# plugin-local Composer autoloader.
CMD ["php", "-d", "auto_prepend_file=/opt/grav/vendor/autoload.php", "vendor/bin/phpunit"]

FROM php:7.4-apache

# ユーザの登録
ARG WWWGROUP
RUN groupadd --force -g $WWWGROUP vessel
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 vessel

ENV APACHE_RUN_USER vessel
ENV APACHE_RUN_GROUP vessel

# タイムゾーンの設定
ENV TZ=Asia/Tokyo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

WORKDIR /var/www/html

# 設定ファイルのコピー
# php.ini-development
COPY ./php.ini /usr/local/etc/php/php.ini
# php.ini上書き用
COPY ./99-custom.ini /usr/local/etc/php/conf.d/99-custom.ini
# VirtualHost用
COPY ./000-default.conf /etc/apache2/sites-enabled/

RUN apt-get update \
    && apt-get install -y zlib1g-dev libpq-dev \
    libzip-dev zip unzip \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    libicu-dev \
    libonig-dev \
    mariadb-client \
    && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath mbstring mysqli pdo_mysql gd intl zip \
    && pecl install xdebug redis \
    && docker-php-ext-enable xdebug redis \
    && docker-php-source delete \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && a2enmod rewrite

# composer のインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node.jsのインストール
COPY --from=node:16 /usr/local/bin/ /usr/local/bin/
COPY --from=node:16 /usr/local/lib/node_modules /usr/local/lib/node_modules

# sendmail関数をMailhog用にする関数をインストール
RUN curl -sSL https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 -o mhsendmail \
    && chmod +x mhsendmail \
    && mv mhsendmail /usr/local/bin/mhsendmail

COPY entrypoint.sh /var/tmp
CMD bash -E /var/tmp/entrypoint.sh && /bin/bash

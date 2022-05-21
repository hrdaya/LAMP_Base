FROM php:7.4-apache

# ユーザの登録
ARG WWWGROUP
RUN groupadd --force -g $WWWGROUP vessel \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 vessel

# タイムゾーンの設定
ARG TZ
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 設定ファイルのコピー
# php.ini-development
COPY ./php.ini /usr/local/etc/php/php.ini
# php.ini上書き用
COPY ./99-custom.ini /usr/local/etc/php/conf.d/99-custom.ini
# VirtualHost用
COPY ./000-default.conf /etc/apache2/sites-enabled/

RUN apt-get update \
    && apt-get install -y \
    # PostgreSQLクライアント(psql) ※必要なときのみインストールで良い
    # libpq-dev postgresql-client \
    # MySQLクライアント(mysql) ※必要なときのみインストールで良い
    # mariadb-client \
    # Redisクライアント(redis-cli) ※必要なときのみインストールで良い
    # redis-tools \
    # ICU(Unicode関連ライブラリ)
    libicu-dev \
    # 鬼車(正規表現ライブラリ)
    libonig-dev \
    # ZIP関連
    libzip-dev zip unzip \
    # GDの最小構成(PNG)
    zlib1g-dev libpng-dev \
    # GDのフォント描画(FreeType --with-freetype)
    libfreetype6-dev \
    # GDのJPEG(JPEG --with-jpeg)
    libjpeg62-turbo-dev \
    # GDのWebP(WebP --with-webp)
    libwebp-dev \
    # PHPのGDの設定
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    # PHPのモジュールのインストール
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    gd \
    intl \
    mbstring \
    mysqli \
    pdo_mysql \
    # pgsql \
    # pdo_pgsql \
    zip \
    # xdebugとphpredisのインストール
    && pecl install xdebug redis \
    # xdebugとphpredisの有効化
    && docker-php-ext-enable xdebug redis \
    # 不要になったものを削除
    && docker-php-source delete \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    # ApacheのRewriteモジュールの有効化
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

# entrypoint.shの実行
COPY entrypoint.sh /var/tmp
CMD bash -E /var/tmp/entrypoint.sh && /bin/bash

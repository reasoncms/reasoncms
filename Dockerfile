FROM php:5.6-apache

MAINTAINER Tom Brice <tbrice@carleton.edu>

# install dependencies
# Install other needed extensions
RUN apt-get update && apt-get install -y libfreetype6 git-core mysql-client imagemagick libjpeg62-turbo libmcrypt4 libpng12-0 sendmail gettext-base --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN buildDeps=" \
        libfreetype6-dev \
        libjpeg-dev \
        libldap2-dev \
        libmcrypt-dev \
        libpng12-dev \
        zlib1g-dev \
        libmagickwand-dev \
        libcurl4-openssl-dev \
        libtidy-dev \
        gettext-base \
    "; \
    set -x \
    && apt-get update && apt-get install -y $buildDeps --no-install-recommends && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --enable-gd-native-ttf --with-jpeg-dir=/usr/lib/x86_64-linux-gnu --with-png-dir=/usr/lib/x86_64-linux-gnu --with-freetype-dir=/usr/lib/x86_64-linux-gnu \
    && docker-php-ext-install gd \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
    && docker-php-ext-install ldap \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install mcrypt \
    && docker-php-ext-install mysql mysqli \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-install zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install curl \
    && docker-php-ext-install tidy

# configure xdebug
RUN echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/xdebug.ini \
  && echo "xdebug.remote_autostart = 0" >> /usr/local/etc/php/conf.d/xdebug.ini \
  && echo "xdebug.remote_connect_back = 0" >> /usr/local/etc/php/conf.d/xdebug.ini \
  && echo "xdebug.remote_port = 9000" >> /usr/local/etc/php/conf.d/xdebug.ini

# configure apache
RUN rm /etc/apache2/sites-enabled/*
RUN a2enmod rewrite

ARG web_root_path=/var/www
ARG reason_package_path=/var/reason_package

COPY . ${reason_package_path}
WORKDIR ${reason_package_path}

RUN [ -d ${web_root_path} ] || mkdir ${web_root_path}

RUN ln -s ${reason_package_path}/reason_4.0/www/ ${web_root_path}/reason \
  && ln -s ${reason_package_path}/www/ ${web_root_path}/reason_package \
  && ln -s ${reason_package_path}/thor/ ${web_root_path}/thor \
  && ln -s ${reason_package_path}/loki_2.0/ ${web_root_path}/loki_2.0 \
  && ln -s ${reason_package_path}/flvplayer/ ${web_root_path}/flvplayer \
  && ln -s ${reason_package_path}/jquery/ ${web_root_path}/jquery \
  && ln -s ${reason_package_path}/date_picker/ ${web_root_path}/date_picker

RUN chown -R www-data:www-data ${web_root_path} \
  && chown -R www-data:www-data ${reason_package_path}/reason_4.0/data/ \
  && chmod -R 0777 ${web_root_path} \
  && chmod -R 0777 ${reason_package_path}/reason_4.0/data/

# setup command
COPY docker/docker-entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80



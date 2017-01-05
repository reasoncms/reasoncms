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

# configure apache
RUN rm /etc/apache2/sites-enabled/*
RUN a2enmod rewrite

# setup command
COPY docker/docker-entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["sh", "-c", "bin/www"]

EXPOSE 80

COPY . /usr/src/app
WORKDIR /usr/src/app

RUN mkdir /usr/src/root
RUN ln -s /usr/src/app/www            /usr/src/root/reason_package
RUN ln -s /usr/src/app/reason_4.0/www /usr/src/root/reason

RUN chown www-data -R /usr/src/root/                         && \
    chown www-data -R /usr/src/app/reason_4.0/data/csv_data/ && \
    chown www-data -R /usr/src/app/reason_4.0/data/logs/     && \
    chown www-data -R /usr/src/app/reason_4.0/data/assets/   && \
    chown www-data -R /usr/src/app/reason_4.0/data/images/   && \
    chown www-data -R /usr/src/app/reason_4.0/data/tmp/      && \
    chown www-data -R /usr/src/app/reason_4.0/data/cache/    && \
    chown www-data -R /usr/src/root/reason/tmp/              && \
    chown www-data -R /usr/src/app/reason_4.0/data/geocodes/


FROM ubuntu:14.04

MAINTAINER Quinn Shanahan <quinn@tastehoneyco.com>

# install dependencies
RUN apt-get update
RUN apt-get -y install git apache2 libapache2-mod-php5 php5-mysql php5-gd php-pear php-apc curl gettext-base

# configure apache
RUN rm /etc/apache2/sites-enabled/*
RUN ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/

# setup command
ADD docker-entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["sh", "-c", "bin/www"]

EXPOSE 80

ADD . /usr/src/app
WORKDIR /usr/src/app

RUN mkdir /usr/src/root
RUN ln -s /usr/src/app/www            /usr/src/root/reason_package
RUN ln -s /usr/src/app/reason_4.0/www /usr/src/root/reason

RUN apt-get -y install php5-curl mysql-client-core-5.6 tidy

RUN chown www-data -R /usr/src/root/                         && \
    chown www-data -R /usr/src/app/reason_4.0/data/csv_data/ && \
    chown www-data -R /usr/src/app/reason_4.0/data/logs/     && \
    chown www-data -R /usr/src/app/reason_4.0/data/assets/   && \
    chown www-data -R /usr/src/app/reason_4.0/data/images/   && \
    chown www-data -R /usr/src/app/reason_4.0/data/tmp/      && \
    chown www-data -R /usr/src/app/reason_4.0/data/cache/    && \
    chown www-data -R /usr/src/root/reason/tmp/              && \
    chown www-data -R /usr/src/app/reason_4.0/data/geocodes/

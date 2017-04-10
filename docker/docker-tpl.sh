#!/bin/bash
set -e

# move settings into place
cp -R docker/settings ${REASON_SETTINGS_PATH}

# copy the dbs.xml into place, substitution on ENV vars takes place
cat docker/dbs.xml.tmpl | envsubst > ${REASON_SETTINGS_PATH}/dbs.xml

# copy files as needed.
# This is done here and not in the Dockerfile becuase this might be specific tot he environment
# No substitution takes place here now, but it could in the future.
cp docker/php.ini /usr/local/etc/php/php.ini
cp docker/000-default.conf /etc/apache2/sites-enabled/

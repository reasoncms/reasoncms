#!/bin/bash
set -e

apache_conf_tpl=$(mktemp -t apache_conf_tpl.XXXXXXXX) || exit 1
    dbs_xml_tpl=$(mktemp -t     dbs_xml_tpl.XXXXXXXX) || exit 1

cat << EOF > $(echo $apache_conf_tpl)
ServerRoot "/etc/apache2"
Mutex file:\${APACHE_LOCK_DIR} default
PidFile \${APACHE_PID_FILE}
Timeout 300
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5
AccessFileName .htaccess
ErrorLog "|/bin/more"

# These need to be set in /etc/apache2/envvars
User \${APACHE_RUN_USER}
Group \${APACHE_RUN_GROUP}

IncludeOptional mods-enabled/*.load
IncludeOptional mods-enabled/*.conf
IncludeOptional conf-enabled/*.conf
Include ports.conf

<Directory />
  Options FollowSymLinks
  AllowOverride None
  Require all denied
</Directory>

<Directory /usr/src/root/>
  Options Indexes FollowSymLinks
  AllowOverride All
  Require all granted
</Directory>

<FilesMatch "^\.ht">
  Require all denied
</FilesMatch>

<VirtualHost *:80>
  DocumentRoot /usr/src/root
  # ErrorLog ${APACHE_LOG_DIR}/error.log
  # CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

php_value include_path "..:/usr/share/php:/usr/share/pear:/usr/src/app/"
EOF

cat << EOF > $(echo $dbs_xml_tpl)
<?xml version="1.0" encoding="ISO-8859-15"?>
<!-- WARNING *** ENSURE THIS FILE IS NOT WEB ACCESSIBLE *** -->
<databases>
  <database>
  <connection_name>reason_connection</connection_name>
  <db>reason</db>
  <user>admin</user>
  <password>\$MYSQL_ENV_MYSQL_PASS</password>
  <host>\$MYSQL_PORT_3306_TCP_ADDR</host>
  </database>

  <database>
  <connection_name>thor_connection</connection_name>
  <db>reason</db>
  <user>admin</user>
  <password>\$MYSQL_ENV_MYSQL_PASS</password>
  <host>\$MYSQL_PORT_3306_TCP_ADDR</host>
  </database>
</databases>
EOF

# no subbing in apache config for now at least.
cat $apache_conf_tpl            > apache.conf
cat     $dbs_xml_tpl | envsubst > settings/dbs.xml

./install.sh

cat reason_4.0/data/dbs/reason4.7.sql | mysql -h$MYSQL_PORT_3306_TCP_ADDR -uadmin -p$MYSQL_ENV_MYSQL_PASS

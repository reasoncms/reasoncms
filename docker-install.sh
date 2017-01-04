#!/usr/bin/env bash

reason_version="4.7"

mysql -u$MYSQL_USER -p$MYSQL_PASSWORD -h$MYSQL_HOST $MYSQL_DATABASE < "reason_4.0/data/dbs/reason$reason_version.sql"

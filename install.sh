#!/bin/bash

echo "Welcome to Reason";
echo "================="
echo "This script will walk you through the first step of installing Reason."
echo "DO NOT RUN THIS SCRIPT ON UPGRADES!"
echo "Do you wish to continue? (y/n)"
read ans

case $ans in
Y|y) ;;
[Yy][Ee][Ss]) ;;
N|n) exit ;;
[Nn][Oo]) exit ;;
*) echo "Invalid command"
esac

if ! which mysql 1> /dev/null
then
  echo "No Mysql client was not found in your path."
  echo "Enter the path to the mysql client"
  echo "For example /Applications/MAMP/Library/bin/mysql"
  read mysqlcmd
else
  mysqlcmd="mysql"
fi

if ! which mysqladmin 1> /dev/null
then
  echo "Mysqladmin was not found in your path."
  echo "Enter the path to the mysqladmin"
  echo "For example /Applications/MAMP/Library/bin/mysqladmin"
  read mysqladmincmd
else
  mysqladmincmd="mysqladmin"
fi

echo " "
echo "Where's your web root?";
echo "Please supply a path from the root of your server"
echo "DO NOT include a trailing slash."
echo "For example /var/www/htdocs"
read webdir

ln -s $PWD/reason_4.0/www/ $webdir/reason
ln -s $PWD/www/ $webdir/reason_package
ln -s $PWD/thor/ $webdir/thor

echo "Symlinks created"

echo " "
echo "Enter the name of your mysql server."
echo "If you wish to use mysql on this server, enter localhost"
read mysqlhost

echo " "
echo "If you have an account that can create databases and users,"
echo "this script can create the users and databases for you."
echo "If not, you'll need to create a database and user for Reason."
echo "Should this script try to create your database and user? (y/n)"
read permission

case $permission in
Y|y)
    echo "Enter the name of the database Reason should create:"
    read mysqldb
    
    echo "Enter a mysql user which can create databases:"
    read mysqlroot
    
    echo "Enter the mysql password for $mysqlroot:"
    read mysqlrootpassy
    
    #create mysql database and user now
    $mysqladmincmd -u$mysqlroot -p$mysqlrootpassy create $mysqldb;
    
    echo "Database created."
    echo " "
    echo "It's not recommended to run Reason as the root user"
    echo "Enter a mysql username to use to run Reason:"
    read mysqluser
    
    echo "Enter a password for your Reason user:"
    read mysqlpassy
    
    case $mysqlhost in
    localhost)
      mysqlfrom="localhost"
    ;;
    *)
      mysqlfrom="*"
    ;;
    esac
    
    $mysqlcmd -u$mysqlroot -p$mysqlrootpassy -Bse "GRANT ALL ON $mysqldb.* to $mysqluser@$mysqlfrom identified by '$mysqlpassy';"
    
;;
N|n) 
    echo " "
    echo "Enter the name of the database you have created for Reason:"
    read mysqldb
    
    echo "Enter the mysql username Reason should use:"
    read mysqluser
    
    echo "Enter the mysql password for $mysqluser:"
    read mysqlpassy
;;
*) echo "Invalid command"
esac

$mysqlcmd -u$mysqluser -p$mysqlpassy -h$mysqlhost $mysqldb < $PWD/reason_4.0/data/dbs/reason4.3.sql

sed -e "s/<db>reason/<db>$mysqldb/g" -e "s/<db>thor/<db>$mysqldb/g" -e "s/reason_user/$mysqluser/g" -e "s/some_password/$mysqlpassy/g" -e "s/your.mysql.hostname.or.ip.address/$mysqlhost/g" $PWD/settings/dbs.xml.sample > $PWD/settings/dbs.xml

echo " "
echo "Database complete."

echo " "
echo "Reason will only output errors to ip addresses configured in"
echo "settings/error_handler_settings.php"
echo "Enter the ip address of your developer's workstation"
read devip

sed "s/put_your_ip_here/$devip/g" $PWD/settings/error_handler_settings.php.sample > $PWD/settings/error_handler_settings.php

echo "Great, the nerdy bits are done. Continue the installer by visiting /reason/setup.php on your Web site."

#! /bin/bash
cat /vagrant/reason_4.0/data/dbs/reason4.4.sql | mysql -u reason_user --password=some_password -h 127.0.0.1 reason
touch /home/vagrant/dbs_written
#!/bin/bash
set -e

cat docker/dbs.xml.tmpl | envsubst > settings/dbs.xml

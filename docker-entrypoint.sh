#!/bin/bash
set -e

./docker-tpl.sh

exec "$@"

#!/bin/bash
set -e

$PWD/docker/docker-tpl.sh

exec "$@"

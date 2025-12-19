#!/bin/sh
set -e

#if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
##	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
##	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var
#fi

exec docker-php-entrypoint "$@"

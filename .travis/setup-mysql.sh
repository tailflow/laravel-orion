#/bin/sh
export DB_DATABASE="orion"
export DB_USERNAME="travis"
mysql -e 'CREATE DATABASE orion;'
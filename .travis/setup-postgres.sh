#/bin/sh
export DB_DATABASE="orion"
export DB_USERNAME="postgres"
psql -c 'create database orion;' -U postgres
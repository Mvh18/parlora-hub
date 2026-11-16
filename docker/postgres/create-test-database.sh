#!/bin/sh
# Runs once, when Compose creates the postgres volume: adds the database the
# test suite uses (phpunit.xml: hub_test) next to the development one.
set -eu

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" \
  -c "CREATE DATABASE hub_test OWNER \"$POSTGRES_USER\""

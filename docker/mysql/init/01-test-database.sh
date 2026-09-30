#!/bin/sh
# Creates the database used by the Symfony test env (Doctrine appends "_test")
# and grants the app user access to it. MySQL runs this only when the data
# volume is first initialised; for an existing volume run `make test-db`.
set -e

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\`;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_test\`.* TO '${MYSQL_USER}'@'%';
SQL

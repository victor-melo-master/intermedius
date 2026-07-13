#!/bin/bash
set -e

: "${MYSQL_ROOT_PASSWORD:?Falta la variable MYSQL_ROOT_PASSWORD}"
: "${MYSQL_DATABASE:?Falta la variable MYSQL_DATABASE}"

echo "=== Inicializando base de datos Intermedius ==="

mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$MYSQL_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

echo "=== Base de datos creada/verificada ==="

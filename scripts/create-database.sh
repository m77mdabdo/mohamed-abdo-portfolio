#!/usr/bin/env bash
# Creates the project database and two least-privilege users on the local XAMPP MariaDB,
# then writes the generated passwords into .env (never into Git).
#
#   bash scripts/create-database.sh
#
# You are asked once for the MariaDB root password (press Enter if root has none).
# Safe to re-run: existing database and users are kept, passwords are rotated and .env is updated.
set -euo pipefail

cd "$(dirname "$0")/.."

MYSQL_BIN="${MYSQL_BIN:-/Applications/XAMPP/xamppfiles/bin/mysql}"
SOCKET="${DB_SOCKET:-/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock}"
DB="mohamed_abdo_portfolio"
APP_USER="map_app"
MIGRATE_USER="map_migrate"

[ -x "$MYSQL_BIN" ] || { echo "MariaDB client not found at $MYSQL_BIN"; exit 1; }
[ -S "$SOCKET" ] || { echo "MariaDB socket not found at $SOCKET (is XAMPP MySQL running?)"; exit 1; }
[ -f .env ] || cp .env.example .env

APP_PASS="$(openssl rand -base64 33 | tr -dc 'A-Za-z0-9' | head -c 32)"
MIGRATE_PASS="$(openssl rand -base64 33 | tr -dc 'A-Za-z0-9' | head -c 32)"

read -r -s -p "MariaDB root password (Enter if none): " ROOT_PASS; echo

MYSQL_PWD="$ROOT_PASS" "$MYSQL_BIN" --socket="$SOCKET" -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`${DB}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '$APP_USER'@'localhost' IDENTIFIED BY '$APP_PASS';
ALTER USER '$APP_USER'@'localhost' IDENTIFIED BY '$APP_PASS';
CREATE USER IF NOT EXISTS '$MIGRATE_USER'@'localhost' IDENTIFIED BY '$MIGRATE_PASS';
ALTER USER '$MIGRATE_USER'@'localhost' IDENTIFIED BY '$MIGRATE_PASS';

-- Runtime user: data only, on this project's database only.
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES, LOCK TABLES ON \`$DB\`.* TO '$APP_USER'@'localhost';
-- Migration / backup user: schema changes on this project's databases only
-- (the main one, the test one and short-lived restore-check copies named ${DB}_*).
GRANT ALL PRIVILEGES ON \`$DB\`.* TO '$MIGRATE_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`mohamed\_abdo\_portfolio\_%\`.* TO '$MIGRATE_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

set_env() {
  local key="$1" value="$2"
  if grep -q "^${key}=" .env; then
    sed -i '' "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}

set_env DB_CONNECTION mariadb
set_env DB_SOCKET "$SOCKET"
set_env DB_DATABASE "$DB"
set_env DB_USERNAME "$APP_USER"
set_env DB_PASSWORD "$APP_PASS"
set_env DB_MIGRATE_USERNAME "$MIGRATE_USER"
set_env DB_MIGRATE_PASSWORD "$MIGRATE_PASS"
set_env DB_TEST_DATABASE "${DB}_test"

echo "Done. Database '$DB' (+ '${DB}_test') and users '$APP_USER' / '$MIGRATE_USER' are ready; passwords were written to .env only."

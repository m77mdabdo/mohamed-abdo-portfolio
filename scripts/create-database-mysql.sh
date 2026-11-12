#!/usr/bin/env bash
# Creates the project database on MySQL 8 (Homebrew mysql@8.4 by default) with two least-privilege users,
# and writes the generated passwords into .env as MYSQL_* entries (never into Git).
#
#   bash scripts/create-database-mysql.sh
#
# You are asked once for the MySQL root password (press Enter if root has none).
# Safe to re-run: existing databases and users are kept, passwords are rotated and .env is updated.
set -euo pipefail

cd "$(dirname "$0")/.."

MYSQL_BIN="${MYSQL_BIN:-/opt/homebrew/opt/mysql@8.4/bin/mysql}"
SOCKET="${MYSQL_SOCKET:-/tmp/mysql-8.4.sock}"
DB="mohamed_abdo_portfolio"
APP_USER="map_app"
MIGRATE_USER="map_migrate"

[ -x "$MYSQL_BIN" ] || { echo "MySQL client not found at $MYSQL_BIN (set MYSQL_BIN=...)"; exit 1; }
[ -S "$SOCKET" ] || { echo "MySQL socket not found at $SOCKET (is MySQL running? brew services start mysql@8.4)"; exit 1; }
[ -f .env ] || cp .env.example .env

APP_PASS="$(openssl rand -base64 33 | tr -dc 'A-Za-z0-9' | head -c 32)"
MIGRATE_PASS="$(openssl rand -base64 33 | tr -dc 'A-Za-z0-9' | head -c 32)"

read -r -s -p "MySQL root password (Enter if none): " ROOT_PASS; echo

MYSQL_PWD="$ROOT_PASS" "$MYSQL_BIN" --socket="$SOCKET" -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`${DB}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '$APP_USER'@'localhost' IDENTIFIED BY '$APP_PASS';
ALTER USER '$APP_USER'@'localhost' IDENTIFIED BY '$APP_PASS';
CREATE USER IF NOT EXISTS '$MIGRATE_USER'@'localhost' IDENTIFIED BY '$MIGRATE_PASS';
ALTER USER '$MIGRATE_USER'@'localhost' IDENTIFIED BY '$MIGRATE_PASS';

-- Runtime user: data only, on this project's database only.
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES, LOCK TABLES ON \`$DB\`.* TO '$APP_USER'@'localhost';
-- Migration / backup user: schema changes on this project's databases only (main, test, restore checks).
GRANT ALL PRIVILEGES ON \`$DB\`.* TO '$MIGRATE_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`mohamed\_abdo\_portfolio\_%\`.* TO '$MIGRATE_USER'@'localhost';
-- mysqldump needs these server-level read rights for consistent backups.
GRANT PROCESS ON *.* TO '$MIGRATE_USER'@'localhost';
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

set_env MYSQL_SOCKET "$SOCKET"
set_env MYSQL_DATABASE "$DB"
set_env MYSQL_USERNAME "$APP_USER"
set_env MYSQL_PASSWORD "$APP_PASS"
set_env MYSQL_MIGRATE_USERNAME "$MIGRATE_USER"
set_env MYSQL_MIGRATE_PASSWORD "$MIGRATE_PASS"
set_env MYSQL_TEST_DATABASE "${DB}_test"

echo "Done. MySQL database '$DB' (+ '${DB}_test') and users '$APP_USER' / '$MIGRATE_USER' are ready; passwords are in .env only."

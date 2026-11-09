#!/usr/bin/env bash
# Sets up e-mail notices for new contact messages using a Gmail account (or any SMTP server).
# The password is typed here and written only to .env on this computer — never to Git.
#
#   bash scripts/configure-mail.sh
#
# Gmail: turn on 2-Step Verification, then create an "App password" at
# https://myaccount.google.com/apppasswords and paste the 16 characters when asked (spaces are fine).
set -euo pipefail
cd "$(dirname "$0")/.."
[ -f .env ] || { echo ".env not found"; exit 1; }

read -r -p "Gmail address that sends the notices: " MAIL_USER
read -r -s -p "Gmail app password (16 characters, input hidden): " MAIL_PASS; echo
MAIL_PASS="$(printf '%s' "$MAIL_PASS" | tr -d ' ')"
read -r -p "SMTP host [smtp.gmail.com]: " HOST; HOST="${HOST:-smtp.gmail.com}"
read -r -p "SMTP port [587]: " PORT; PORT="${PORT:-587}"

set_env() {
  local key="$1" value="$2"
  if grep -q "^${key}=" .env; then
    # use a separator that cannot appear in the values we write
    sed -i '' "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}
set_env MAIL_MAILER smtp
set_env MAIL_SCHEME null
set_env MAIL_HOST "$HOST"
set_env MAIL_PORT "$PORT"
set_env MAIL_USERNAME "$MAIL_USER"
set_env MAIL_PASSWORD "\"$MAIL_PASS\""
set_env MAIL_FROM_ADDRESS "\"$MAIL_USER\""
set_env MAIL_FROM_NAME "\"Mohamed Abdo — Portfolio\""

php artisan config:clear >/dev/null
echo "Saved. Sending a test e-mail…"
php artisan portfolio:mail-test "$MAIL_USER"

#!/usr/bin/env bash
# Starts the site on this Mac with Homebrew PHP (XAMPP's Apache/PHP 8.1 cannot run it):
#   site       http://127.0.0.1:8020/ar  (and /en)
#   dashboard  http://127.0.0.1:8020/admin
# Keeps running until you close the window or press Ctrl+C. Also runs the scheduler (daily backup).
set -euo pipefail
cd "$(dirname "$0")/.."

PHP="${PHP:-}"
for candidate in "$PHP" /opt/homebrew/bin/php /usr/local/bin/php "$(command -v php || true)"; do
  if [ -n "$candidate" ] && [ -x "$candidate" ] && "$candidate" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);'; then PHP="$candidate"; break; fi
done
if [ -z "$PHP" ] || ! "$PHP" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);'; then
  echo "PHP 8.4 or newer was not found. Install it with: brew install php"; exit 1
fi

PORT="${PORT:-8020}"
URL="http://127.0.0.1:${PORT}"
[ -f .env ] || { echo "Missing .env (see README › Install)."; exit 1; }
[ -L public/storage ] || "$PHP" artisan storage:link >/dev/null
[ -f public/build/manifest.json ] || { command -v npm >/dev/null && npm run build >/dev/null; }

if lsof -nP -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
  echo "The site is already running at $URL"
  open "$URL/ar" 2>/dev/null || true
  exit 0
fi

"$PHP" artisan config:clear >/dev/null
echo "Starting the site with $("$PHP" -r 'echo PHP_VERSION;')…"
"$PHP" artisan schedule:work >/dev/null 2>&1 &
SCHEDULER=$!
trap 'kill $SCHEDULER 2>/dev/null || true' EXIT
( sleep 2; open "$URL/ar" 2>/dev/null || true ) &
echo "Site:      $URL/ar  ·  $URL/en"
echo "Dashboard: $URL/admin"
echo "Close this window (or press Ctrl+C) to stop."
"$PHP" artisan serve --host=127.0.0.1 --port="$PORT"

#!/bin/sh
set -e

# Reverb only needs credentials the app and the websocket server agree on, and
# both live in this one container, so blank values are minted at boot
export REVERB_APP_ID="${REVERB_APP_ID:-tidal-ptc}"
export REVERB_APP_KEY="${REVERB_APP_KEY:-$(php -r 'echo bin2hex(random_bytes(16));')}"
export REVERB_APP_SECRET="${REVERB_APP_SECRET:-$(php -r 'echo bin2hex(random_bytes(16));')}"

# Caches bake env values, so they're built at start rather than image build
php artisan optimize --no-interaction
php artisan migrate --force --no-interaction

# ponytail: no supervisor; each loop restarts its process when it exits.
# Octane is PID 1, so if it dies the platform restarts the whole container.
forever() { while :; do "$@" || true; sleep 1; done; }
forever php artisan reverb:start --host=127.0.0.1 --port=8080 &
forever php artisan queue:work database --tries=3 --max-time=3600 &
forever php artisan schedule:work &

exec php artisan octane:frankenphp --host=0.0.0.0 --port=8000 --caddyfile=docker/app-platform/Caddyfile

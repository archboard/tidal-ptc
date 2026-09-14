#!/bin/sh
set -e

# Caches bake env values, so they're built at start rather than image build
php artisan optimize --no-interaction

exec "$@"

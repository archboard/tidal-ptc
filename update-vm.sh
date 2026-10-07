#!/usr/bin/env bash
#
# Updates a Tidal PTC install made by install-vm.sh: pulls the code, rebuilds,
# migrates, re-caches and restarts PHP-FPM, Reverb and the queue worker.
# Leaves Nginx, PostgreSQL, systemd units and .env alone (re-run install-vm.sh for those).
#
#   cd /var/www/tidal-ptc && sudo ./update-vm.sh [--version v1.2.3]

set -euo pipefail

VERSION="${VERSION:-}"
APP_DIR="${APP_DIR:-$PWD}"
APP_USER="${APP_USER:-tidal}"

PHP=8.5
APP_HOME="/home/$APP_USER"

usage() {
    cat <<EOF
Usage: update-vm.sh [options]

  --version REF      Git branch or tag to deploy (default: the current branch)
  --dir PATH         Install directory (default: the current directory)
  -h, --help         Show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --version) VERSION="$2"; shift 2 ;;
        --dir) APP_DIR="$2"; shift 2 ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
    esac
done

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
die() { printf '\033[1;31mError:\033[0m %s\n' "$*" >&2; exit 1; }

as_app() { (cd "$APP_DIR" && runuser -u "$APP_USER" -- env HOME="$APP_HOME" "$@"); }

[[ $EUID -eq 0 ]] || die "Run as root (sudo bash update-vm.sh ...)."
[[ -d "$APP_DIR/.git" ]] || die "$APP_DIR is not a git checkout. Install with install-vm.sh first."

# ---------------------------------------------------------------------------

step "Fetching the code"

# Show the maintenance page while the update runs, and always bring the site back
as_app php artisan down --retry=15 || true
trap 'as_app php artisan up > /dev/null 2>&1 || true' EXIT

as_app git fetch --tags --force origin
if [[ -n "$VERSION" ]]; then
    as_app git checkout -q "$VERSION"
fi
# Branches fast-forward to the remote; tags are already exact
if branch=$(as_app git symbolic-ref -q --short HEAD); then
    as_app git merge -q --ff-only "origin/$branch"
fi
echo "Now at $(as_app git describe --tags --always)."

# ---------------------------------------------------------------------------

step "Building the application"

as_app composer install --no-dev --optimize-autoloader --no-interaction --no-progress
as_app npm ci --no-audit --no-fund
as_app npm run build
as_app php artisan migrate --force --no-interaction
as_app php artisan optimize --no-interaction

# ---------------------------------------------------------------------------

step "Restarting services"

# Long-running workers keep old code in memory, so always restart them
systemctl restart tidal-ptc-reverb tidal-ptc-queue
systemctl reload "php$PHP-fpm"

as_app php artisan up
trap - EXIT

echo
echo "Tidal PTC is up to date."

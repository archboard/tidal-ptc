#!/usr/bin/env bash
#
# Installs (or updates) Tidal PTC on a fresh Debian 12+ / Ubuntu 24.04+ server:
# PHP-FPM, PostgreSQL, Redis, Nginx + Let's Encrypt, Reverb and the queue worker
# under systemd, and the scheduler in cron. See INSTALLATION-VM.md.
#
# Safe to re-run: existing secrets and .env values are kept, and a re-run pulls
# the latest code, rebuilds, migrates and restarts everything.
#
#   curl -fsSL https://raw.githubusercontent.com/archboard/tidal-ptc/main/install-vm.sh \
#     | sudo bash -s -- --domain ptc.example.org --email you@example.org

set -euo pipefail

DOMAIN="${DOMAIN:-}"
EMAIL="${EMAIL:-}"
VERSION="${VERSION:-main}"
REPO="${REPO:-https://github.com/archboard/tidal-ptc.git}"
APP_DIR="${APP_DIR:-/var/www/tidal-ptc}"
APP_USER="${APP_USER:-tidal}"
DB_NAME="${DB_NAME:-tidal_ptc}"
NO_TLS="${NO_TLS:-0}"

PHP=8.5
NODE_MAJOR=24
PG_MAJOR=18
ACME_ROOT=/var/www/letsencrypt
APP_HOME="/home/$APP_USER"
FPM_SOCK="/run/php/$APP_USER.sock"

usage() {
    cat <<EOF
Usage: install-vm.sh --domain ptc.example.org [options]

  --domain DOMAIN    Public hostname (required; prompted for if omitted)
  --email EMAIL      Let's Encrypt expiry notices (optional)
  --version REF      Git branch or tag to deploy (default: main)
  --no-tls           Skip Let's Encrypt and serve plain HTTP
  --dir PATH         Install directory (default: /var/www/tidal-ptc)
  -h, --help         Show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --domain) DOMAIN="$2"; shift 2 ;;
        --email) EMAIL="$2"; shift 2 ;;
        --version) VERSION="$2"; shift 2 ;;
        --no-tls) NO_TLS=1; shift ;;
        --dir) APP_DIR="$2"; shift 2 ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
    esac
done

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33mWarning:\033[0m %s\n' "$*" >&2; }
die() { printf '\033[1;31mError:\033[0m %s\n' "$*" >&2; exit 1; }

# Runs a command as the app user from the app directory with a sane HOME
# (runuser, unlike sudo, is always present on Debian minimal images)
as_app() { (cd "$APP_DIR" && runuser -u "$APP_USER" -- env HOME="$APP_HOME" "$@"); }

# Prompts read from the terminal so they still work under `curl | bash`
ask() {
    local prompt="$1" var="$2"
    if [[ -z "${!var}" && -r /dev/tty ]]; then
        # shellcheck disable=SC2229 # reads into the variable named by $var
        read -rp "$prompt" "$var" < /dev/tty
    fi
}

env_get() { grep -m1 "^$1=" "$APP_DIR/.env" 2>/dev/null | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' || true; }

env_set() {
    local file="$APP_DIR/.env"
    awk -v k="$1" -v v="$2" '
        index($0, k "=") == 1 { print k "=" v; done = 1; next }
        { print }
        END { if (!done) print k "=" v }
    ' "$file" > "$file.tmp" && cat "$file.tmp" > "$file" && rm "$file.tmp"
}

# Only fills a value that is missing or blank, so re-runs never rotate secrets
env_default() { [[ -n "$(env_get "$1")" ]] || env_set "$1" "$2"; }

# ---------------------------------------------------------------------------

step "Checking the system"

[[ $EUID -eq 0 ]] || die "Run as root (sudo bash install-vm.sh ...)."
[[ -r /etc/os-release ]] || die "Cannot read /etc/os-release."
# shellcheck source=/dev/null
. /etc/os-release
case "${ID:-}" in
    debian|ubuntu) ;;
    *) die "Only Debian and Ubuntu are supported (found '${ID:-unknown}')." ;;
esac

ask "Public domain for Tidal PTC (e.g. ptc.example.org): " DOMAIN
[[ -n "$DOMAIN" ]] || die "--domain is required."
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || die "'$DOMAIN' is not a valid hostname."
if [[ "$NO_TLS" != 1 ]]; then
    ask "Email for Let's Encrypt notices (optional, Enter to skip): " EMAIL
fi

export DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a

# ---------------------------------------------------------------------------

step "Installing system packages"

ONDREJ_PPA="https://ppa.launchpadcontent.net/ondrej/php/ubuntu/dists/${VERSION_CODENAME:-}/Release"

# The PPA only publishes for some Ubuntu releases (not 26.04, which ships PHP
# 8.5 itself). An entry for an unpublished release fails every apt update.
if compgen -G "/etc/apt/sources.list.d/ondrej-ubuntu-php-*" > /dev/null && ! curl -fsIo /dev/null "$ONDREJ_PPA"; then
    warn "Removing the ondrej/php PPA: it has no packages for $VERSION_CODENAME."
    rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-*
fi

apt-get update -q
apt-get install -yq ca-certificates curl git unzip gnupg cron openssl

if ! apt-cache show "php$PHP-fpm" > /dev/null 2>&1; then
    if [[ "$ID" == ubuntu ]]; then
        apt-get install -yq software-properties-common
        if curl -fsIo /dev/null "$ONDREJ_PPA"; then
            add-apt-repository -y ppa:ondrej/php
        else
            add-apt-repository -y universe
        fi
    else
        curl -fsSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
        dpkg -i /tmp/debsuryorg-archive-keyring.deb
        echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $VERSION_CODENAME main" \
            > /etc/apt/sources.list.d/php.list
    fi
    apt-get update -q
    apt-cache show "php$PHP-fpm" > /dev/null 2>&1 || die "PHP $PHP is not available for $PRETTY_NAME."
fi

if ! apt-cache show "postgresql-$PG_MAJOR" > /dev/null 2>&1; then
    apt-get install -yq postgresql-common
    /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y
fi

node_major=$(node -v 2>/dev/null | sed -E 's/^v([0-9]+).*/\1/' || true)
if [[ -z "$node_major" || "$node_major" -lt 20 ]]; then
    curl -fsSL "https://deb.nodesource.com/setup_$NODE_MAJOR.x" | bash -
fi

apt-get install -yq \
    "php$PHP-fpm" "php$PHP-cli" "php$PHP-pgsql" "php$PHP-redis" "php$PHP-intl" \
    "php$PHP-zip" "php$PHP-mbstring" "php$PHP-xml" "php$PHP-curl" "php$PHP-bcmath" \
    "postgresql-$PG_MAJOR" redis-server nginx certbot nodejs

if ! command -v composer > /dev/null; then
    expected=$(curl -fsSL https://composer.github.io/installer.sig)
    curl -fsSLo /tmp/composer-setup.php https://getcomposer.org/installer
    actual=$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")
    [[ "$expected" == "$actual" ]] || die "Composer installer checksum mismatch."
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm /tmp/composer-setup.php
fi

systemctl enable --now postgresql redis-server nginx "php$PHP-fpm" cron

# ---------------------------------------------------------------------------

step "Fetching the code ($VERSION)"

id "$APP_USER" > /dev/null 2>&1 || useradd --create-home --home-dir "$APP_HOME" --shell /bin/bash "$APP_USER"
mkdir -p "$APP_DIR"
chown "$APP_USER:$APP_USER" "$APP_DIR"

if [[ -d "$APP_DIR/.git" ]]; then
    UPDATING=1
    # Show the maintenance page while the update runs
    as_app php artisan down --retry=15 || true
    trap 'as_app php artisan up > /dev/null 2>&1 || true' EXIT
    as_app git fetch --tags --force origin
else
    UPDATING=0
    [[ -z "$(ls -A "$APP_DIR")" ]] || die "$APP_DIR exists and is not a git checkout."
    runuser -u "$APP_USER" -- git clone "$REPO" "$APP_DIR"
fi

as_app git checkout -q "$VERSION"
# Branches fast-forward to the remote; tags are already exact
if as_app git symbolic-ref -q HEAD > /dev/null; then
    as_app git merge -q --ff-only "origin/$VERSION"
fi

# ---------------------------------------------------------------------------

step "Configuring PostgreSQL"

if [[ ! -f "$APP_DIR/.env" ]]; then
    install -o "$APP_USER" -g "$APP_USER" -m 640 "$APP_DIR/.env.example" "$APP_DIR/.env"
    # First-run defaults that admins may later tune; never reset on re-runs
    env_set LOG_CHANNEL daily
    env_set LOG_LEVEL warning
fi

env_default DB_PASSWORD "$(openssl rand -hex 24)"
db_password=$(env_get DB_PASSWORD)

runuser -u postgres -- psql -v ON_ERROR_STOP=1 -q <<SQL
SELECT 'CREATE ROLE "$APP_USER" LOGIN' WHERE NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '$APP_USER')\gexec
ALTER ROLE "$APP_USER" PASSWORD '$db_password';
SELECT 'CREATE DATABASE "$DB_NAME" OWNER "$APP_USER"' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DB_NAME')\gexec
SQL

# ---------------------------------------------------------------------------

step "Configuring PHP-FPM"

cat > "/etc/php/$PHP/fpm/pool.d/tidal-ptc.conf" <<EOF
[tidal-ptc]
user = $APP_USER
group = $APP_USER
listen = $FPM_SOCK
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
EOF

systemctl reload "php$PHP-fpm"

# ---------------------------------------------------------------------------

step "Configuring Nginx"

CERT_DIR="/etc/letsencrypt/live/$DOMAIN"
mkdir -p "$ACME_ROOT"

write_nginx() {
    local tls="$1"
    local app_server
    app_server=$(cat <<EOF
    root $APP_DIR/public;
    index index.php;
    charset utf-8;

    location ^~ /.well-known/acme-challenge/ {
        root $ACME_ROOT;
    }

    # Reverb websockets
    location ~ ^/apps?(/|\$) {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \$connection_upgrade;
        proxy_set_header Host \$host;
        proxy_read_timeout 86400;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|\$) {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$FPM_SOCK;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
EOF
)

    {
        cat <<'EOF'
# Managed by install-vm.sh; re-running the script overwrites this file.
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

EOF
        if [[ "$tls" == 1 ]]; then
            cat <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

    location ^~ /.well-known/acme-challenge/ {
        root $ACME_ROOT;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    # The old http2 syntax still works on Nginx 1.22/1.24 (Debian 12, Ubuntu 24.04)
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $DOMAIN;

    ssl_certificate     $CERT_DIR/fullchain.pem;
    ssl_certificate_key $CERT_DIR/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

$app_server
}
EOF
        else
            cat <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

$app_server
}
EOF
        fi
    } > /etc/nginx/sites-available/tidal-ptc

    ln -sf /etc/nginx/sites-available/tidal-ptc /etc/nginx/sites-enabled/tidal-ptc
    rm -f /etc/nginx/sites-enabled/default
    nginx -t -q || die "Nginx rejected the generated config (/etc/nginx/sites-available/tidal-ptc)."
    systemctl reload nginx
}

if ufw status 2> /dev/null | grep -q "Status: active"; then
    ufw allow 'Nginx Full' > /dev/null
fi

TLS=0
if [[ "$NO_TLS" != 1 ]]; then
    if [[ -f "$CERT_DIR/fullchain.pem" ]]; then
        TLS=1
    else
        # Serve the ACME challenge over HTTP first, then switch to HTTPS
        write_nginx 0
        email_args=(--register-unsafely-without-email)
        [[ -n "$EMAIL" ]] && email_args=(--email "$EMAIL")
        if certbot certonly --webroot -w "$ACME_ROOT" -d "$DOMAIN" --non-interactive --agree-tos \
            "${email_args[@]}" --deploy-hook "systemctl reload nginx"; then
            TLS=1
        else
            warn "Could not get a certificate for $DOMAIN. Check that its DNS points at this server and ports 80/443 are open, then re-run this script. Continuing over plain HTTP."
        fi
    fi
fi
write_nginx "$TLS"

if [[ "$TLS" == 1 ]]; then scheme=https; port=443; else scheme=http; port=80; fi

# ---------------------------------------------------------------------------

step "Writing .env"

env_default APP_KEY "base64:$(openssl rand -base64 32)"
env_default REVERB_APP_ID "$(shuf -i 100000-999999 -n 1)"
env_default REVERB_APP_KEY "$(openssl rand -hex 16)"
env_default REVERB_APP_SECRET "$(openssl rand -hex 32)"

env_set APP_ENV production
env_set APP_DEBUG false
env_set APP_URL "$scheme://$DOMAIN"
env_set DB_CONNECTION pgsql
env_set DB_HOST 127.0.0.1
env_set DB_PORT 5432
env_set DB_DATABASE "$DB_NAME"
env_set DB_USERNAME "$APP_USER"
env_set CACHE_DRIVER redis
env_set SESSION_DRIVER redis
env_set QUEUE_CONNECTION redis
env_set REDIS_HOST 127.0.0.1
env_set BROADCAST_CONNECTION reverb
env_set REVERB_HOST "$DOMAIN"
env_set REVERB_PORT "$port"
env_set REVERB_SCHEME "$scheme"
env_set REVERB_SERVER_HOST 127.0.0.1
env_set REVERB_SERVER_PORT 8080

chown "$APP_USER:$APP_USER" "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"

# ---------------------------------------------------------------------------

step "Building the application"

as_app composer install --no-dev --optimize-autoloader --no-interaction --no-progress
as_app npm ci --no-audit --no-fund
as_app npm run build
as_app php artisan migrate --force --no-interaction
as_app php artisan optimize --no-interaction

# ---------------------------------------------------------------------------

step "Configuring background services"

cat > /etc/systemd/system/tidal-ptc-reverb.service <<EOF
[Unit]
Description=Tidal PTC Reverb websocket server
After=network.target redis-server.service

[Service]
User=$APP_USER
Group=$APP_USER
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8080
Restart=always
RestartSec=3
LimitNOFILE=10000

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/systemd/system/tidal-ptc-queue.service <<EOF
[Unit]
Description=Tidal PTC queue worker
After=network.target redis-server.service postgresql.service

[Service]
User=$APP_USER
Group=$APP_USER
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan queue:work redis --queue=default,sis_sync --tries=3 --max-time=3600
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1" | crontab -u "$APP_USER" -

systemctl daemon-reload
systemctl enable -q tidal-ptc-reverb tidal-ptc-queue
# Long-running workers keep old code in memory, so always restart them
systemctl restart tidal-ptc-reverb tidal-ptc-queue
systemctl reload "php$PHP-fpm"

if [[ "$UPDATING" == 1 ]]; then
    as_app php artisan up
    trap - EXIT
fi

# ---------------------------------------------------------------------------

step "Checking the site"

sleep 2
status=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$DOMAIN:$port:127.0.0.1" "$scheme://$DOMAIN/up" || true)
if [[ "$status" == 200 ]]; then
    echo "$scheme://$DOMAIN/up returned 200."
else
    warn "$scheme://$DOMAIN/up returned '$status'. Check $APP_DIR/storage/logs and /var/log/nginx/error.log."
fi

cat <<EOF

Tidal PTC is installed at $APP_DIR.

Next steps:
  1. Visit $scheme://$DOMAIN/install to download the PowerSchool plugin and create the first administrator.
  2. Add POWERSCHOOL_ADDRESS, POWERSCHOOL_CLIENT_ID and POWERSCHOOL_CLIENT_SECRET to $APP_DIR/.env,
     then re-run this script (or: sudo -u $APP_USER php $APP_DIR/artisan optimize && systemctl restart tidal-ptc-queue).

Re-run this script at any time to update to the latest $VERSION.
EOF

#!/usr/bin/env bash
#
# Installs (or updates) Tidal PTC with Docker on a fresh Debian / Ubuntu server,
# end to end. See INSTALLATION.md.
#
#   1. Check the system and collect the domain, email and reverse proxy choice
#   2. Install Docker Engine and the Compose plugin (if missing)
#   3. Download docker-compose.yml and bind its ports to localhost
#   4. Write .env (secrets are generated once and kept on re-runs)
#   5. Install and configure the reverse proxy with Let's Encrypt (Caddy or Nginx)
#   6. Pull the images and start the stack
#   7. Check that the site responds
#
# Safe to re-run: existing secrets and .env values are kept, and a re-run
# downloads the latest Compose file, pulls new images and restarts the stack.
#
#   curl -fsSL https://raw.githubusercontent.com/archboard/tidal-ptc/main/setup-docker.sh \
#     | sudo bash -s -- --domain ptc.example.org --email you@example.org

set -euo pipefail

DOMAIN="${DOMAIN:-}"
EMAIL="${EMAIL:-}"
PROXY="${PROXY:-}"
POWERSCHOOL_ADDRESS="${POWERSCHOOL_ADDRESS:-}"
TIDAL_PTC_VERSION="${TIDAL_PTC_VERSION:-}"
APP_DIR="${APP_DIR:-/opt/tidal-ptc}"
NO_TLS="${NO_TLS:-0}"

RAW="https://raw.githubusercontent.com/archboard/tidal-ptc/main"
ACME_ROOT=/var/www/letsencrypt
CADDYFILE=/etc/caddy/Caddyfile
NGINX_SITE=/etc/nginx/sites-available/tidal-ptc
MANAGED="# Managed by setup-docker.sh; re-running the script overwrites this file."

usage() {
    cat <<EOF
Usage: setup-docker.sh --domain ptc.example.org [options]

  --domain DOMAIN      Public hostname (required; prompted for if omitted)
  --email EMAIL        Let's Encrypt expiry notices (optional)
  --proxy NAME         Reverse proxy: caddy (default) or nginx
  --powerschool URL    PowerSchool address, e.g. https://ps.example.org (optional)
  --version TAG        Image tag to run (default: latest, which tracks main)
  --dir PATH           Install directory (default: /opt/tidal-ptc)
  --no-tls             Skip Let's Encrypt and serve plain HTTP
  -h, --help           Show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --domain) DOMAIN="$2"; shift 2 ;;
        --email) EMAIL="$2"; shift 2 ;;
        --proxy) PROXY="$2"; shift 2 ;;
        --powerschool) POWERSCHOOL_ADDRESS="$2"; shift 2 ;;
        --version) TIDAL_PTC_VERSION="$2"; shift 2 ;;
        --dir) APP_DIR="$2"; shift 2 ;;
        --no-tls) NO_TLS=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
    esac
done

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33mWarning:\033[0m %s\n' "$*" >&2; }
die() { printf '\033[1;31mError:\033[0m %s\n' "$*" >&2; exit 1; }

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
# 1. Check the system and collect the settings
# ---------------------------------------------------------------------------

step "Checking the system"

[[ $EUID -eq 0 ]] || die "Run as root (curl ... | sudo bash -s -- --domain ...)."
[[ -r /etc/os-release ]] || die "Cannot read /etc/os-release."
# shellcheck source=/dev/null
. /etc/os-release
case "${ID:-}" in
    debian|ubuntu) ;;
    *) die "Only Debian and Ubuntu are supported (found '${ID:-unknown}'). See INSTALLATION.md to install by hand." ;;
esac

# On a re-run, default to the domain and proxy the previous run set up
if [[ -z "$DOMAIN" ]]; then
    DOMAIN="$(env_get APP_URL | sed -E 's|^https?://||; s|/.*$||')"
    [[ "$DOMAIN" == localhost ]] && DOMAIN=
fi
if [[ -z "$PROXY" ]]; then
    if grep -qsF "$MANAGED" "$NGINX_SITE"; then
        PROXY=nginx
    elif grep -qsF "$MANAGED" "$CADDYFILE"; then
        PROXY=caddy
    fi
fi

ask "Public domain for Tidal PTC (e.g. ptc.example.org): " DOMAIN
[[ -n "$DOMAIN" ]] || die "--domain is required."
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || die "'$DOMAIN' is not a valid hostname."

ask "Reverse proxy, caddy or nginx [caddy]: " PROXY
PROXY="${PROXY:-caddy}"
[[ "$PROXY" == caddy || "$PROXY" == nginx ]] || die "--proxy must be caddy or nginx."

if [[ "$NO_TLS" != 1 ]]; then
    ask "Email for Let's Encrypt notices (optional, Enter to skip): " EMAIL
fi

export DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a

apt-get update -q
apt-get install -yq ca-certificates curl gnupg openssl

# ---------------------------------------------------------------------------
# 2. Install Docker Engine and the Compose plugin
# ---------------------------------------------------------------------------

# Follows Docker's official apt repository, which ships the Compose plugin;
# the distro's docker.io package does not
if ! docker compose version > /dev/null 2>&1; then
    step "Installing Docker"

    apt-get remove -yq docker.io docker-doc docker-compose podman-docker containerd runc 2> /dev/null || true
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL "https://download.docker.com/linux/$ID/gpg" -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/$ID $VERSION_CODENAME stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update -q
    apt-get install -yq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

systemctl enable --now docker

# Let the user who ran sudo manage the stack without sudo (takes effect on next login)
if [[ -n "${SUDO_USER:-}" && "$SUDO_USER" != root ]]; then
    usermod -aG docker "$SUDO_USER"
fi

# ---------------------------------------------------------------------------
# 3. Download the Compose file and bind its ports to localhost
# ---------------------------------------------------------------------------

step "Preparing $APP_DIR"

mkdir -p "$APP_DIR"
cd "$APP_DIR"

# A source checkout (Dockerfile present) builds its own images and keeps its
# own Compose file; otherwise always fetch the latest published one
if [[ -f Dockerfile ]]; then
    BUILD_FROM_SOURCE=1
else
    BUILD_FROM_SOURCE=0
    curl -fsSLO "$RAW/docker-compose.yml"
fi

# Only the reverse proxy may reach the app: the Compose file sets
# TRUSTED_PROXIES=*, which is only safe when the ports aren't public.
# `!override` replaces the default port list instead of appending to it.
cat > docker-compose.override.yml <<EOF
$MANAGED
services:
  app:
    ports: !override
      - "127.0.0.1:8000:8000"
  reverb:
    ports: !override
      - "127.0.0.1:8080:8080"
EOF

# ---------------------------------------------------------------------------
# 4. Write .env
# ---------------------------------------------------------------------------

step "Writing .env"

[[ -f .env ]] || curl -fsSL -o .env "$RAW/.env.example"
chmod 600 .env

env_default APP_KEY "base64:$(openssl rand -base64 32)"
env_default DB_PASSWORD "$(openssl rand -hex 24)"
env_default REVERB_APP_ID "$((RANDOM % 900000 + 100000))"
env_default REVERB_APP_KEY "$(openssl rand -hex 16)"
env_default REVERB_APP_SECRET "$(openssl rand -hex 32)"

env_set APP_ENV production
env_set APP_DEBUG false
[[ -n "$POWERSCHOOL_ADDRESS" ]] && env_set POWERSCHOOL_ADDRESS "$POWERSCHOOL_ADDRESS"
[[ -n "$TIDAL_PTC_VERSION" ]] && env_set TIDAL_PTC_VERSION "$TIDAL_PTC_VERSION"

# APP_URL and REVERB_* depend on whether TLS succeeds, so they're set in step 5

if [[ -n "${SUDO_USER:-}" && "$SUDO_USER" != root ]]; then
    chown "$SUDO_USER:" "$APP_DIR" "$APP_DIR"/docker-compose*.yml "$APP_DIR/.env"
fi

# ---------------------------------------------------------------------------
# 5. Reverse proxy and Let's Encrypt
# ---------------------------------------------------------------------------

step "Configuring $PROXY"

# Only one proxy can own ports 80 and 443
other_proxy=$([[ "$PROXY" == caddy ]] && echo nginx || echo caddy)
if systemctl is-active -q "$other_proxy" 2> /dev/null; then
    warn "Stopping and disabling $other_proxy so $PROXY can use ports 80 and 443."
    systemctl disable --now -q "$other_proxy"
fi

if ufw status 2> /dev/null | grep -q "Status: active"; then
    ufw allow 80/tcp > /dev/null
    ufw allow 443/tcp > /dev/null
fi

TLS=0

# --- 5a. Caddy: obtains and renews the certificate itself ------------------

setup_caddy() {
    # Caddy's official apt repository, per caddyserver.com/docs/install
    if ! command -v caddy > /dev/null; then
        apt-get install -yq debian-keyring debian-archive-keyring apt-transport-https
        curl -fsSL https://dl.cloudsmith.io/public/caddy/stable/gpg.key \
            | gpg --batch --yes --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
        curl -fsSL https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt \
            > /etc/apt/sources.list.d/caddy-stable.list
        apt-get update -q
        apt-get install -yq caddy
    fi

    local site="$DOMAIN" global=""
    if [[ "$NO_TLS" == 1 ]]; then
        site="http://$DOMAIN"
    else
        TLS=1
        [[ -n "$EMAIL" ]] && global=$'{\n    email '"$EMAIL"$'\n}\n\n'
    fi

    cat > "$CADDYFILE" <<EOF
$MANAGED
$global$site {
    # Reverb websockets
    @reverb path /app /app/* /apps /apps/*
    reverse_proxy @reverb 127.0.0.1:8080

    # Application
    reverse_proxy 127.0.0.1:8000
}
EOF

    caddy validate --config "$CADDYFILE" --adapter caddyfile > /dev/null || die "Caddy rejected $CADDYFILE."
    systemctl enable -q caddy
    systemctl reload-or-restart caddy
}

# --- 5b. Nginx: certificate from certbot over the HTTP-01 webroot -----------

write_nginx() {
    local tls="$1"
    local locations
    locations=$(cat <<'EOF'
    client_max_body_size 20m;

    location ^~ /.well-known/acme-challenge/ {
        root ACME_ROOT;
    }

    # Reverb websockets
    location ~ ^/apps?(/|$) {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }

    # Application
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
    }
EOF
)
    locations="${locations//ACME_ROOT/$ACME_ROOT}"

    {
        cat <<EOF
$MANAGED
map \$http_upgrade \$connection_upgrade {
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

    ssl_certificate     /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

$locations
}
EOF
        else
            cat <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

$locations
}
EOF
        fi
    } > "$NGINX_SITE"

    ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/tidal-ptc
    rm -f /etc/nginx/sites-enabled/default
    nginx -t -q || die "Nginx rejected the generated config ($NGINX_SITE)."
    systemctl enable -q nginx
    systemctl reload-or-restart nginx
}

setup_nginx() {
    # certbot's package installs a systemd timer that renews certificates
    apt-get install -yq nginx certbot
    mkdir -p "$ACME_ROOT"

    if [[ "$NO_TLS" == 1 ]]; then
        write_nginx 0
        return
    fi

    if [[ ! -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]]; then
        # Serve the ACME challenge over HTTP first, then switch to HTTPS
        write_nginx 0
        local email_args=(--register-unsafely-without-email)
        [[ -n "$EMAIL" ]] && email_args=(--email "$EMAIL")
        if ! certbot certonly --webroot -w "$ACME_ROOT" -d "$DOMAIN" --non-interactive --agree-tos \
            "${email_args[@]}" --deploy-hook "systemctl reload nginx"; then
            warn "Could not get a certificate for $DOMAIN. Check that its DNS points at this server and ports 80/443 are open, then re-run this script. Continuing over plain HTTP."
            return
        fi
    fi

    TLS=1
    write_nginx 1
}

"setup_$PROXY"

# --- 5c. Point the app and websockets at the public URL ---------------------

if [[ "$TLS" == 1 ]]; then scheme=https; port=443; else scheme=http; port=80; fi

env_set APP_URL "$scheme://$DOMAIN"
# The public hostname browsers connect to, not the container
env_set REVERB_HOST "$DOMAIN"
env_set REVERB_PORT "$port"
env_set REVERB_SCHEME "$scheme"

# ---------------------------------------------------------------------------
# 6. Pull the images and start the stack
# ---------------------------------------------------------------------------

step "Starting the stack"

if [[ "$BUILD_FROM_SOURCE" == 1 ]]; then
    docker compose build
else
    docker compose pull -q
fi

# Migrations run when the app container starts; --wait blocks until it's healthy
docker compose up -d --remove-orphans --wait --wait-timeout 300 \
    || die "The stack did not become healthy. Check: docker compose -f $APP_DIR/docker-compose.yml logs app"

# ---------------------------------------------------------------------------
# 7. Check that the site responds through the proxy
# ---------------------------------------------------------------------------

step "Checking the site"

# Caddy may still be fetching its certificate, so retry for up to a minute
if curl -fsS -o /dev/null --retry 12 --retry-delay 5 --retry-all-errors \
    --resolve "$DOMAIN:$port:127.0.0.1" "$scheme://$DOMAIN/up"; then
    echo "$scheme://$DOMAIN/up returned 200."
else
    warn "$scheme://$DOMAIN/up did not return 200. Check: journalctl -u $PROXY, and docker compose logs app (from $APP_DIR)."
fi

cat <<EOF

Tidal PTC is running from $APP_DIR behind $PROXY.

Next steps:
  1. Visit $scheme://$DOMAIN/install to download the PowerSchool plugin and create the first administrator.
  2. Add POWERSCHOOL_CLIENT_ID and POWERSCHOOL_CLIENT_SECRET to $APP_DIR/.env once the plugin is
     installed in PowerSchool, then run \`docker compose up -d\` from $APP_DIR.

Re-run this script at any time to update to the latest images.
EOF
if [[ -n "${SUDO_USER:-}" && "$SUDO_USER" != root ]]; then
    echo "Log out and back in so $SUDO_USER can run docker without sudo."
fi

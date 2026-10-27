#!/usr/bin/env bash
#
# Installs Docker Engine (Debian / Ubuntu) if it's missing, then writes a
# production .env for the Docker Compose stack. Run it as a regular user with
# sudo, from the directory that will hold docker-compose.yml. See INSTALLATION.md.
#
# Safe to re-run: existing secrets are kept, and only the domain-derived
# values are rewritten.
#
#   curl -fsSL https://raw.githubusercontent.com/archboard/tidal-ptc/main/setup-docker.sh \
#     | bash -s -- --domain ptc.example.org

set -euo pipefail

DOMAIN="${DOMAIN:-}"
POWERSCHOOL_ADDRESS="${POWERSCHOOL_ADDRESS:-}"
NO_TLS="${NO_TLS:-0}"
RAW="https://raw.githubusercontent.com/archboard/tidal-ptc/main"

usage() {
    cat <<EOF
Usage: setup-docker.sh --domain ptc.example.org [options]

  --domain DOMAIN          Public hostname (required; prompted for if omitted)
  --powerschool URL        PowerSchool address, e.g. https://ps.example.org (optional)
  --no-tls                 Serve plain HTTP instead of HTTPS
  -h, --help               Show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --domain) DOMAIN="$2"; shift 2 ;;
        --powerschool) POWERSCHOOL_ADDRESS="$2"; shift 2 ;;
        --no-tls) NO_TLS=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
    esac
done

die() { printf '\033[1;31mError:\033[0m %s\n' "$*" >&2; exit 1; }

# Prompts read from the terminal so they still work under `curl | bash`
ask() {
    local prompt="$1" var="$2"
    if [[ -z "${!var}" && -r /dev/tty ]]; then
        # shellcheck disable=SC2229 # reads into the variable named by $var
        read -rp "$prompt" "$var" < /dev/tty
    fi
}

env_get() { grep -m1 "^$1=" .env 2>/dev/null | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' || true; }

env_set() {
    awk -v k="$1" -v v="$2" '
        index($0, k "=") == 1 { print k "=" v; done = 1; next }
        { print }
        END { if (!done) print k "=" v }
    ' .env > .env.tmp && cat .env.tmp > .env && rm .env.tmp
}

# Only fills a value that is missing or blank, so re-runs never rotate secrets
env_default() { [[ -n "$(env_get "$1")" ]] || env_set "$1" "$2"; }

SUDO=
[[ $EUID -eq 0 ]] || SUDO=sudo

# Follows Docker's official apt repository, which ships the Compose plugin;
# the distro's docker.io package does not
install_docker() {
    [[ -r /etc/os-release ]] || die "Cannot read /etc/os-release; install Docker by hand."
    # shellcheck source=/dev/null
    . /etc/os-release
    case "${ID:-}" in
        debian|ubuntu) ;;
        *) die "Docker can only be installed automatically on Debian and Ubuntu (found '${ID:-unknown}'). See INSTALLATION.md." ;;
    esac

    echo "Installing Docker Engine..."
    $SUDO apt-get remove -y docker.io docker-doc docker-compose podman-docker containerd runc 2> /dev/null || true
    $SUDO apt-get update
    $SUDO apt-get install -y ca-certificates curl openssl
    $SUDO install -m 0755 -d /etc/apt/keyrings
    $SUDO curl -fsSL "https://download.docker.com/linux/$ID/gpg" -o /etc/apt/keyrings/docker.asc
    $SUDO chmod a+r /etc/apt/keyrings/docker.asc
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/$ID $VERSION_CODENAME stable" \
        | $SUDO tee /etc/apt/sources.list.d/docker.list > /dev/null
    $SUDO apt-get update
    $SUDO apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    $SUDO systemctl enable --now docker
    [[ $EUID -eq 0 ]] || $SUDO usermod -aG docker "$USER"
    DOCKER_INSTALLED=1
}

DOCKER_INSTALLED=0
docker compose version > /dev/null 2>&1 || install_docker
command -v openssl > /dev/null || die "openssl is required."

[[ -f docker-compose.yml ]] || curl -fsSLO "$RAW/docker-compose.yml"
[[ -f .env ]] || curl -fsSL -o .env "$RAW/.env.example"
chmod 600 .env

[[ -z "$DOMAIN" ]] && DOMAIN="$(env_get APP_URL | sed -E 's|^https?://||; s|/.*$||')"
[[ "$DOMAIN" == localhost ]] && DOMAIN=
ask "Public domain for Tidal PTC (e.g. ptc.example.org): " DOMAIN
[[ -n "$DOMAIN" ]] || die "A domain is required (--domain ptc.example.org)."

if [[ "$NO_TLS" == 1 ]]; then scheme=http; port=80; else scheme=https; port=443; fi

env_default APP_KEY "base64:$(openssl rand -base64 32)"
env_default DB_PASSWORD "$(openssl rand -hex 24)"
env_default REVERB_APP_ID "$((RANDOM % 900000 + 100000))"
env_default REVERB_APP_KEY "$(openssl rand -hex 16)"
env_default REVERB_APP_SECRET "$(openssl rand -hex 32)"

env_set APP_ENV production
env_set APP_DEBUG false
env_set APP_URL "$scheme://$DOMAIN"
env_set REVERB_HOST "$DOMAIN"
env_set REVERB_PORT "$port"
env_set REVERB_SCHEME "$scheme"
[[ -n "$POWERSCHOOL_ADDRESS" ]] && env_set POWERSCHOOL_ADDRESS "$POWERSCHOOL_ADDRESS"

cat <<EOF

.env is ready for $scheme://$DOMAIN.

Next:
  docker compose pull && docker compose up -d
$([[ "$DOCKER_INSTALLED" == 1 && $EUID -ne 0 ]] && printf '\nDocker was just installed: log out and back in (or run `newgrp docker`)\nso your user can run docker without sudo.\n')

Add POWERSCHOOL_CLIENT_ID and POWERSCHOOL_CLIENT_SECRET to .env once the
plugin is installed in PowerSchool, then run \`docker compose up -d\` again.
EOF

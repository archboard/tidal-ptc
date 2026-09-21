# Installing Tidal PTC with Docker

This guide takes a fresh Linux server to a running Tidal PTC behind HTTPS. The Compose stack in this repository runs everything the application needs: the app on Octane (FrankenPHP), Reverb for websockets, a queue worker, the scheduler, PostgreSQL and Redis. The only thing you add is a reverse proxy that terminates TLS.

## 1. Requirements

- A Linux server (2 GB RAM is plenty to start) with a public IP.
- A DNS `A` record for your domain (e.g. `ptc.example.org`) pointing at the server.
- [Docker Engine](https://docs.docker.com/engine/install/) with the Compose plugin (`docker compose version` should work).
- PowerSchool admin access to install the Tidal PTC plugin and copy its client ID and secret. You can do this after the app is running; the installer at `/install` lets you download the plugin.

### Installing Docker (Debian / Ubuntu)

Skip this if `docker compose version` already works. These steps follow Docker's official apt repository, which ships the Compose plugin; the `docker.io` package in the distro repos does not.

```sh
# Remove any distro-packaged Docker that may conflict
sudo apt-get remove -y docker.io docker-doc docker-compose podman-docker containerd runc 2>/dev/null

# Add Docker's GPG key and apt repository
sudo apt-get update
sudo apt-get install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
. /etc/os-release
sudo curl -fsSL "https://download.docker.com/linux/$ID/gpg" -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/$ID $VERSION_CODENAME stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine and the Compose plugin
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Start on boot and let your user run docker without sudo
sudo systemctl enable --now docker
sudo usermod -aG docker "$USER"
```

Log out and back in for the group change to take effect, then verify:

```sh
docker compose version
docker run --rm hello-world
```

`$ID` resolves to `debian` or `ubuntu`. On derivatives (Linux Mint, Pop!_OS, Raspberry Pi OS) set `ID` and `VERSION_CODENAME` by hand to the upstream Debian/Ubuntu release they are based on.

## 2. Get the Compose file

Prebuilt images are published to `ghcr.io/archboard/tidal-ptc` on every release, so you only need the Compose file and an `.env`, not the source.

```sh
sudo mkdir -p /opt/tidal-ptc && sudo chown "$USER" /opt/tidal-ptc
cd /opt/tidal-ptc
curl -fsSLO https://raw.githubusercontent.com/archboard/tidal-ptc/main/docker-compose.yml
curl -fsSL -o .env https://raw.githubusercontent.com/archboard/tidal-ptc/main/.env.example
```

`latest` tracks `main`. To pin a release, add `TIDAL_PTC_VERSION=1.2.3` to `.env` (tags match the GitHub releases).

<details>
<summary>Building from source instead</summary>

Clone the repository into `/opt/tidal-ptc` and use `docker compose build` / `docker compose up -d --build` wherever this guide says `pull`. Everything else is identical.

</details>

## 3. Configure `.env`

```sh
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
```

Then edit `.env` and set the following. Everything not listed keeps a working default, and the Compose file overrides the database, Redis, cache, session and queue hosts for you.

| Variable | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://ptc.example.org` |
| `DB_PASSWORD` | A long random password (`openssl rand -hex 24`). Required; Compose refuses to start without it. |
| `REVERB_APP_ID` | Any number, e.g. `openssl rand -hex 4 \| tr -dc 0-9` or just `100001` |
| `REVERB_APP_KEY` | `openssl rand -hex 16` |
| `REVERB_APP_SECRET` | `openssl rand -hex 32` |
| `REVERB_HOST` | `ptc.example.org` — the public hostname browsers connect to, **not** the container |
| `REVERB_PORT` | `443` |
| `REVERB_SCHEME` | `https` |
| `POWERSCHOOL_ADDRESS` | `https://powerschool.example.org` |
| `POWERSCHOOL_CLIENT_ID` / `POWERSCHOOL_CLIENT_SECRET` | From the plugin, once installed in PowerSchool |

Email (SMTP) is configured later in the app's tenant settings page, so the `MAIL_*` values can stay as they are.

## 4. Pull and start

```sh
docker compose pull
docker compose up -d
```

Migrations run automatically when the `app` service starts. Check that everything is healthy:

```sh
docker compose ps
docker compose logs -f app
```

The app now listens on `http://<server-ip>:8000` and Reverb on `:8080`. Don't expose those publicly; put the reverse proxy from the next section in front of them.

### Bind the published ports to localhost

By default Compose publishes ports `8000` and `8080` on every interface. Create a `docker-compose.override.yml` so they are only reachable by the reverse proxy on the same host. Compose merges this file automatically.

```yaml
services:
  app:
    ports:
      - "127.0.0.1:8000:8000"
  reverb:
    ports:
      - "127.0.0.1:8080:8080"
```

Then `docker compose up -d` to apply it. This matters: the Compose file sets `TRUSTED_PROXIES=*` so the app reads client IPs from `X-Forwarded-For`, which is only safe when the ports can't be reached except through your proxy. If your firewall (`ufw`, security groups, etc.) is on, only ports `80` and `443` need to be open.

## 5. Reverse proxy

Two paths need routing:

| Public path | Upstream | Notes |
|---|---|---|
| `/app`, `/apps` | `127.0.0.1:8080` (Reverb) | Websockets — the proxy must pass `Upgrade`/`Connection` headers |
| everything else | `127.0.0.1:8000` (app) | |

Pick one of the following.

### Option A: Caddy (recommended)

Caddy obtains and renews the Let's Encrypt certificate itself and proxies websockets without extra configuration.

<details>
<summary>Installing Caddy (Debian / Ubuntu)</summary>

From Caddy's official apt repository, per [caddyserver.com/docs/install](https://caddyserver.com/docs/install#debian-ubuntu-raspbian):

```sh
sudo apt-get install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt-get update
sudo apt-get install -y caddy
```

The package installs a `caddy` systemd service that is enabled and running. Ports `80` and `443` must be reachable from the internet for certificate issuance.

</details>

Replace `/etc/caddy/Caddyfile` with:

```caddyfile
ptc.example.org {
    @reverb path /app /app/* /apps /apps/*
    reverse_proxy @reverb 127.0.0.1:8080
    reverse_proxy 127.0.0.1:8000
}
```

```sh
sudo systemctl reload caddy
```

### Option B: Nginx

<details>
<summary>Installing Nginx and Certbot (Debian / Ubuntu)</summary>

```sh
sudo apt-get update
sudo apt-get install -y nginx certbot python3-certbot-nginx
sudo systemctl enable --now nginx
```

Certbot's package installs a systemd timer that renews certificates automatically; `sudo certbot renew --dry-run` confirms it works once the site below is set up.

</details>

Create `/etc/nginx/sites-available/tidal-ptc`:

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

server {
    listen 80;
    server_name ptc.example.org;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ptc.example.org;

    # Filled in by certbot
    ssl_certificate     /etc/letsencrypt/live/ptc.example.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ptc.example.org/privkey.pem;

    client_max_body_size 20m;

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
}
```

```sh
sudo ln -s /etc/nginx/sites-available/tidal-ptc /etc/nginx/sites-enabled/
sudo certbot --nginx -d ptc.example.org
sudo nginx -t && sudo systemctl reload nginx
```

### Verify

- `https://ptc.example.org/up` returns `200`.
- Open the browser dev tools on any page: there should be a websocket connection to `wss://ptc.example.org/app/<REVERB_APP_KEY>` with status `101`. If it fails, `REVERB_HOST`/`REVERB_PORT`/`REVERB_SCHEME` don't match the public URL or the proxy isn't forwarding `Upgrade`.

## 6. Finish the installation

Visit `https://ptc.example.org/install`. The installer walks you through downloading the PowerSchool plugin, entering the tenant details and creating the first administrator. If you didn't have the PowerSchool credentials in step 3, add them to `.env` now and run `docker compose up -d` so the containers pick them up.

## 7. Day-to-day operations

| Task | Command (from `/opt/tidal-ptc`) |
|---|---|
| Run Artisan | `docker compose exec app php artisan <command>` |
| Tail logs | `docker compose logs -f app queue reverb` |
| Update to a new version | `curl -fsSLO https://raw.githubusercontent.com/archboard/tidal-ptc/main/docker-compose.yml && docker compose pull && docker compose up -d` |
| Change `.env` | Edit, then `docker compose up -d` (containers are recreated with the new values) |
| Back up the database | `docker compose exec -T postgres sh -c 'pg_dump -U $POSTGRES_USER $POSTGRES_DB' \| gzip > ptc-$(date +%F).sql.gz` |
| Restore a backup | `gunzip -c ptc-YYYY-MM-DD.sql.gz \| docker compose exec -T postgres sh -c 'psql -U $POSTGRES_USER $POSTGRES_DB'` |

Data lives in the `postgres` and `redis` Docker volumes; `docker compose down` keeps them, `docker compose down -v` deletes them. Scheduled reminders and cleanup run in the `scheduler` service, so no host cron entry is needed.

# Installing Tidal PTC on a VM (without Docker)

This guide takes a fresh Debian 12/13 or Ubuntu 24.04/26.04 server to a running Tidal PTC behind HTTPS, with every piece installed as a regular system package: Nginx and PHP-FPM serve the app, PostgreSQL and Redis run locally, and systemd keeps Reverb (websockets) and the queue worker running. Prefer containers? See [INSTALLATION.md](INSTALLATION.md).

## Quick install

[`install-vm.sh`](install-vm.sh) automates every step below. Point your domain's DNS at the server first, then run:

```sh
curl -fsSL https://raw.githubusercontent.com/archboard/tidal-ptc/main/install-vm.sh \
  | sudo bash -s -- --domain ptc.example.org --email you@example.org
```

Options: `--version v1.2.3` deploys a tag instead of `main`, `--no-tls` skips Let's Encrypt (e.g. behind a load balancer that terminates TLS), `--dir` changes the install directory. Run with `--help` to see them all.

The script is safe to re-run: it keeps existing secrets and `.env` values, and pulls, rebuilds, migrates and restarts everything. Re-run it to update, after editing `.env`, or after fixing DNS if the certificate request failed. Then finish with [step 10](#10-finish-the-installation).

The rest of this guide is the same install done by hand.

## Manual install

Run the commands as a normal user with `sudo`. Replace `ptc.example.org` with your domain throughout.

## 1. Requirements

- A Debian 12/13 or Ubuntu 24.04/26.04 server (2 GB RAM is plenty to start) with a public IP.
- A DNS `A` record for your domain pointing at the server.
- Ports `80` and `443` open to the internet (for Let's Encrypt and visitors). Nothing else needs to be public.
- PowerSchool admin access to install the Tidal PTC plugin and copy its client ID and secret. You can do this after the app is running; the installer at `/install` lets you download the plugin.

## 2. Install the system packages

### PHP 8.5

Ubuntu 26.04 ships PHP 8.5 in `universe`. Older releases need Ondřej Surý's repository; add only the one for your OS.

```sh
sudo apt-get update
sudo apt-get install -y ca-certificates curl git unzip lsb-release

# Ubuntu 26.04: nothing to add (universe is enabled by default)

# Ubuntu 24.04 (the PPA has no packages for 26.04 and breaks apt update there)
sudo apt-get install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php

# Debian 12 / 13
curl -sSL https://packages.sury.org/php/README.txt | sudo bash -x

sudo apt-get update
sudo apt-get install -y php8.5-fpm php8.5-cli php8.5-pgsql php8.5-redis php8.5-intl \
  php8.5-zip php8.5-mbstring php8.5-xml php8.5-curl php8.5-bcmath
```

### Composer

```sh
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Don't use the distro `composer` package; it pulls in the distro's older PHP.

### Node.js (for building front-end assets)

```sh
curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### PostgreSQL 18

From the official PostgreSQL apt repository:

```sh
sudo apt-get install -y postgresql-common
sudo /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y
sudo apt-get install -y postgresql-18
```

Create the database and its user. Keep the password for `.env`.

```sh
DB_PASSWORD=$(openssl rand -hex 24); echo "$DB_PASSWORD"
sudo -u postgres psql -c "CREATE ROLE tidal LOGIN PASSWORD '$DB_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE tidal_ptc OWNER tidal;"
```

PostgreSQL only listens on `localhost` by default, which is what we want.

### Redis

```sh
sudo apt-get install -y redis-server
sudo systemctl enable --now redis-server
```

The packaged config binds to `127.0.0.1` only, so no password is needed for a single-server install.

### Nginx and Certbot

```sh
sudo apt-get install -y nginx certbot python3-certbot-nginx
sudo systemctl enable --now nginx
```

## 3. Get the code

The app, PHP-FPM, the queue worker and Reverb all run as one dedicated `tidal` user, so files written by any of them (logs, cache) are writable by the others.

```sh
sudo useradd --create-home --shell /bin/bash tidal
sudo mkdir -p /var/www/tidal-ptc
sudo chown tidal:tidal /var/www/tidal-ptc
sudo -u tidal git clone https://github.com/archboard/tidal-ptc.git /var/www/tidal-ptc
```

`main` is the latest code. To run a release instead, `sudo -u tidal git -C /var/www/tidal-ptc checkout v1.2.3` (tags match the GitHub releases).

## 4. Configure `.env`

```sh
sudo -iu tidal
cd /var/www/tidal-ptc
cp .env.example .env
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
```

Edit `.env` and set the following. Everything not listed keeps a working default.

| Variable | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://ptc.example.org` |
| `LOG_CHANNEL` | `daily` (rotates `storage/logs` and keeps 14 days) |
| `LOG_LEVEL` | `warning` |
| `DB_DATABASE` / `DB_USERNAME` | `tidal_ptc` / `tidal` |
| `DB_PASSWORD` | The password from step 2 |
| `CACHE_DRIVER` / `SESSION_DRIVER` / `QUEUE_CONNECTION` | `redis` |
| `REVERB_APP_ID` | Any number, e.g. `100001` |
| `REVERB_APP_KEY` | `openssl rand -hex 16` |
| `REVERB_APP_SECRET` | `openssl rand -hex 32` |
| `REVERB_HOST` | `ptc.example.org` — the public hostname browsers connect to |
| `REVERB_PORT` | `443` |
| `REVERB_SCHEME` | `https` |
| `REVERB_SERVER_HOST` | `127.0.0.1` (add this line; keeps the websocket server off the public interface) |
| `POWERSCHOOL_ADDRESS` | `https://powerschool.example.org` |
| `POWERSCHOOL_CLIENT_ID` / `POWERSCHOOL_CLIENT_SECRET` | From the plugin, once installed in PowerSchool |

Leave `TRUSTED_PROXIES` unset: Nginx talks to PHP-FPM directly over FastCGI, so the app already sees the real client IP and HTTPS.

Email (SMTP) is configured later in the app's tenant settings page, so the `MAIL_*` values can stay as they are.

## 5. Build and migrate

Still as the `tidal` user in `/var/www/tidal-ptc`:

```sh
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize
exit
```

`php artisan optimize` caches config and routes, so rerun it after every `.env` change.

## 6. PHP-FPM pool

Give the app its own pool running as `tidal`. Create `/etc/php/8.5/fpm/pool.d/tidal-ptc.conf`:

```ini
[tidal-ptc]
user = tidal
group = tidal
listen = /run/php/tidal-ptc.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
```

```sh
sudo systemctl restart php8.5-fpm
```

Raise `pm.max_children` if you have the RAM (roughly 50 MB per child).

## 7. Nginx

Create `/etc/nginx/sites-available/tidal-ptc`. Start with plain HTTP; Certbot adds the TLS bits in the next step.

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

server {
    listen 80;
    server_name ptc.example.org;
    root /var/www/tidal-ptc/public;
    index index.php;

    charset utf-8;

    # Reverb websockets
    location ~ ^/apps?(/|$) {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_read_timeout 86400;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/tidal-ptc.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Only `index.php` is executed; any other `.php` file under `public/` is served as a 404.

```sh
sudo ln -s /etc/nginx/sites-available/tidal-ptc /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d ptc.example.org --redirect
```

Certbot rewrites the server block to listen on `443` with the new certificate, adds the HTTP→HTTPS redirect, and installs a systemd timer that renews automatically. `sudo certbot renew --dry-run` confirms renewal works.

## 8. Background services

### Reverb and the queue worker

Create `/etc/systemd/system/tidal-ptc-reverb.service`:

```ini
[Unit]
Description=Tidal PTC Reverb websocket server
After=network.target redis-server.service

[Service]
User=tidal
Group=tidal
WorkingDirectory=/var/www/tidal-ptc
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8080
Restart=always
RestartSec=3
LimitNOFILE=10000

[Install]
WantedBy=multi-user.target
```

And `/etc/systemd/system/tidal-ptc-queue.service`:

```ini
[Unit]
Description=Tidal PTC queue worker
After=network.target redis-server.service postgresql.service

[Service]
User=tidal
Group=tidal
WorkingDirectory=/var/www/tidal-ptc
ExecStart=/usr/bin/php artisan queue:work redis --queue=default,sis_sync --tries=3 --max-time=3600
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

`--queue=default,sis_sync` processes mail and notifications before PowerSchool syncs. `--max-time=3600` makes the worker exit hourly to release memory; systemd starts a fresh one.

```sh
sudo systemctl daemon-reload
sudo systemctl enable --now tidal-ptc-reverb tidal-ptc-queue
```

### Scheduler

Reminders and activity-log pruning run from the Laravel scheduler. Add it to the `tidal` user's crontab:

```sh
echo '* * * * * cd /var/www/tidal-ptc && php artisan schedule:run >> /dev/null 2>&1' | sudo crontab -u tidal -
```

## 9. Verify

- `https://ptc.example.org/up` returns `200`.
- `systemctl status tidal-ptc-reverb tidal-ptc-queue` shows both `active (running)`.
- Open the browser dev tools on any page: there should be a websocket connection to `wss://ptc.example.org/app/<REVERB_APP_KEY>` with status `101`. If it fails, `REVERB_HOST`/`REVERB_PORT`/`REVERB_SCHEME` don't match the public URL, `php artisan optimize` wasn't rerun after editing `.env`, or Reverb isn't running.
- Errors land in `/var/www/tidal-ptc/storage/logs/` and `/var/log/nginx/error.log`.

## 10. Finish the installation

Visit `https://ptc.example.org/install`. The installer walks you through downloading the PowerSchool plugin, entering the tenant details and creating the first administrator. If you didn't have the PowerSchool credentials in step 4, add them to `.env` now and see "Change `.env`" below.

## 11. Day-to-day operations

Run app commands as the `tidal` user: `sudo -iu tidal`, then `cd /var/www/tidal-ptc`.

| Task | Command |
|---|---|
| Run Artisan | `sudo -u tidal php /var/www/tidal-ptc/artisan <command>` |
| Tail logs | `tail -f /var/www/tidal-ptc/storage/logs/laravel-*.log` / `journalctl -fu tidal-ptc-queue -u tidal-ptc-reverb` |
| Change `.env` | Edit, `sudo -u tidal php /var/www/tidal-ptc/artisan optimize`, then `sudo systemctl reload php8.5-fpm && sudo systemctl restart tidal-ptc-queue tidal-ptc-reverb` |
| Back up the database | `sudo -u postgres pg_dump tidal_ptc \| gzip > ptc-$(date +%F).sql.gz` |
| Restore a backup | `gunzip -c ptc-YYYY-MM-DD.sql.gz \| sudo -u postgres psql tidal_ptc` |

### Updating to a new version

```sh
sudo -iu tidal
cd /var/www/tidal-ptc
php artisan down
git pull                      # or: git fetch --tags && git checkout v1.2.3
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan up
exit

sudo systemctl reload php8.5-fpm
sudo systemctl restart tidal-ptc-queue tidal-ptc-reverb
```

Restarting the queue worker and Reverb is required: they are long-running processes and keep the old code in memory until restarted.

To hear about new versions, watch the repository's releases on GitHub (**Watch → Custom → Releases**).

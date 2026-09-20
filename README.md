# Tidal PTC

Tidal PTC (parent-teacher conferences) is [Archboard's](https://archboard.io) first open source project. It is a web application that allows parents and guardians to schedule parent-teacher conferences with their children's teachers. It has direct integration with PowerSchool, which makes it easy for IT admins to set up and get running.

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=5902fc4786e5&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)
[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/archboard/tidal-ptc/tree/main&refcode=5902fc4786e5)

## Installation

Requirements: PHP 8.5, PostgreSQL, Node 20+, a PowerSchool plugin (client ID/secret) for SSO and data sync.

```sh
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Point a web server at `public/`, then visit `/install` to configure the tenant and create the first administrator.

### Scheduled tasks and email

Add the scheduler to cron so reminders go out and the activity log is pruned:

```
* * * * * cd /path/to/tidal-ptc && php artisan schedule:run >> /dev/null 2>&1
```

Email is sent through the SMTP settings configured on the tenant settings page. `QUEUE_CONNECTION=sync` (the default) sends mail inline; set a real queue driver and run `php artisan queue:work` to send in the background.

### Docker

The repository ships a production Compose stack: the app on [Laravel Octane](https://laravel.com/docs/octane) (FrankenPHP), Reverb for websockets, a queue worker, the scheduler, PostgreSQL and Redis.

```sh
cp .env.example .env
# Set APP_URL, APP_ENV=production, APP_DEBUG=false, DB_PASSWORD, the REVERB_* values
# and the PowerSchool credentials, plus a key:
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
docker compose up -d --build
```

Migrations run automatically when the `app` service starts. Put a TLS-terminating reverse proxy in front and route `/` to port `8000` and `/app`, `/apps` (websockets) to port `8080`; `REVERB_HOST`/`REVERB_PORT`/`REVERB_SCHEME` must describe the proxy's public websocket address. Run Artisan with `docker compose exec app php artisan …`. After pulling a new version, `docker compose up -d --build` restarts every service on the new image.

### DigitalOcean App Platform

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/archboard/tidal-ptc/tree/main&refcode=5902fc4786e5)

The button deploys `.do/deploy.template.yaml`: a single container built from the `app-platform` stage of the Dockerfile (Octane, Reverb, the queue worker and the scheduler in one process tree) plus a dev PostgreSQL database. Before deploying, fill in `APP_KEY` (`echo "base64:$(openssl rand -base64 32)"`) and the PowerSchool credentials; everything else has a working default. Websockets are served through the app's own domain, so no extra routing is needed.

### Local development

`php artisan migrate --seed` creates a tenant on `APP_URL` with an admin (`admin@example.com`), a teacher, a guardian and sample time slots — all with the password `password`. Run `npm run enums` after changing a `#[PublishEnum]` enum to regenerate the JavaScript copies.

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=5902fc4786e5&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)

## License

Tidal PTC is licensed under the [GNU GPLv3](LICENSE.md) license. You can read more about it on [GNU's website](https://www.gnu.org/licenses/quick-guide-gplv3.html).

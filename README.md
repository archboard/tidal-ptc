# Tidal PTC

Tidal PTC (parent-teacher conferences) is [Archboard's](https://archboard.io) first open source project. It's a web application for scheduling parent-teacher conferences: admins and teachers create time slots, and parents and guardians log in to book them.

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=5902fc4786e5&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)

## Features

- **PowerSchool integration.** Install the plugin and students, teachers and contacts sync automatically, with single sign-on for everyone. No CSV exports or temporary passwords.
- **Flexible time slots.** A wide range of configuration options for how slots are created, booked and managed.
- **Translators.** Families can request a translator, and admins manage the requests.
- **Online meetings.** Conferences can be held online as well as in person.
- **Email reminders** for upcoming conferences.
- **Multilingual.** Available in English, Chinese, Korean, Japanese, Spanish and Arabic.
- **Free to self-host.** No subscription costs, and you get the complete application.

## Installation

Requirements: PHP 8.5, PostgreSQL, Redis, Node 20+, and a PowerSchool plugin (client ID/secret) for SSO and data sync.

- **[Docker](INSTALLATION.md)** (recommended): a production Compose stack with images published to `ghcr.io/archboard/tidal-ptc`.
- **[VM without Docker](INSTALLATION-VM.md)**: Nginx, PHP-FPM, PostgreSQL, Redis and systemd services on a fresh server.

Either way, finish by visiting `/install` to configure the tenant and create the first administrator. To hear about new versions, watch the repository's releases on GitHub (**Watch → Custom → Releases**).

### Rotating the app key

`APP_KEY` encrypts every tenant's PowerSchool and SMTP credentials, and it signs sessions and cloud setup links. Keep it only in your host's secret store. If it leaks, or on a schedule, rotate it:

1. Generate a new key with `php artisan key:generate --show`. Set it as `APP_KEY`, move the old key to `APP_PREVIOUS_KEYS` (comma-separated), and deploy. Existing secrets, sessions and links keep working.
2. Run `php artisan key:reencrypt` to rewrite the tenant secrets with the new key.
3. Remove `APP_PREVIOUS_KEYS` and deploy. Everyone is signed out, and unused cloud setup links stop working (reissue them with `php artisan tenant:setup-link`).

## Local development

```sh
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Seeding creates a tenant on `APP_URL` with an admin (`admin@example.com`), a teacher, a guardian and sample time slots, all with the password `password`. Run `npm run enums` after changing a `#[PublishEnum]` enum to regenerate the JavaScript copies.

## License

Tidal PTC is licensed under the [GNU AGPLv3](LICENSE.md) license. You can read more about it on [GNU's website](https://www.gnu.org/licenses/agpl-3.0.html).

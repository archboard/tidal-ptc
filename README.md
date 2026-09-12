# Tidal PTC

Tidal PTC (parent-teacher conferences) is [Archboard's](https://archboard.io) first open source project. It is a web application that allows parents and guardians to schedule parent-teacher conferences with their children's teachers. It has direct integration with PowerSchool, which makes it easy for IT admins to set up and get running.

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

### Local development

`php artisan migrate --seed` creates a tenant on `APP_URL` with an admin (`admin@example.com`), a teacher, a guardian and sample time slots — all with the password `password`. Run `npm run enums` after changing a `#[PublishEnum]` enum to regenerate the JavaScript copies.

## License

Tidal PTC is licensed under the [GNU GPLv3](LICENSE.md) license. You can read more about it on [GNU's website](https://www.gnu.org/licenses/quick-guide-gplv3.html).

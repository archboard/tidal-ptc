/** Placeholders: change these in one place. */
export const SITE_URL = 'https://docs.tidalptc.com';
export const BILLING_URL = 'https://billing.archboard.io/get-started';
export const GITHUB_URL = 'https://github.com/archboard/tidal-ptc';
export const DEMO_URL = 'https://demo.tidalptc.app';

/** Mirrors App\Enums\Product pricing in archboard-billing. Prices are per year, USD. */
export const HOSTING_TIERS = [
  {
    name: 'Self-hosted',
    price: 'Free',
    blurb: 'Run Tidal PTC on your own servers. Your IT team installs, updates and backs it up.',
    features: ['The complete application, AGPL-3.0', 'Docker or VM install', 'Community support on GitHub'],
    cta: 'Read the install guide',
    href: '/installation/requirements/',
  },
  {
    name: 'Cloud',
    price: '$400 + $150 per school a year',
    note: 'Up to $4,000 a year for a district. Single schools pay $450 a year.',
    blurb: 'We host, update and back it up for you. Your schools are scheduling the same day.',
    features: ['Your own subdomain on tidalptc.app', 'Automatic updates and backups', 'PowerSchool SSO and sync'],
    cta: 'Buy hosting',
    href: BILLING_URL,
    badge: 'Most districts',
  },
  {
    name: 'Private dedicated',
    price: '[DEDICATED PRICE]',
    blurb: 'Your own isolated instance on servers reserved for your district. We run it for you.',
    features: ['Isolated servers and database', 'Choose your region', 'Usually ready within 15 minutes'],
    cta: 'Buy hosting',
    href: BILLING_URL,
  },
] as const;

/**
 * Captures light and dark screenshots of the running app into src/assets/screenshots.
 *
 *   npm run screenshots                 # every shot
 *   npm run screenshots -- dashboard    # only shots whose name contains "dashboard"
 *
 * Expects `php artisan migrate:fresh --seed --seeder=DemoSeeder` with DEMO_MODE off,
 * so the password form shows. Override the target with SCREENSHOT_URL.
 */
import { chromium, type Browser, type Page } from 'playwright';
import { mkdir } from 'node:fs/promises';

const BASE_URL = process.env.SCREENSHOT_URL ?? 'https://tidal-ptc.test';
const OUT = new URL('../src/assets/screenshots/', import.meta.url);
const PASSWORD = 'password';
/** DemoSeeder schools' timezone; a mismatch opens the "Update your timezone" modal. */
const TIMEZONE = 'America/Chicago';

/** DemoSeeder accounts. `radio` is the "Log in as" option label. */
const USERS = {
  admin: { email: 'admin@demo.test', radio: 'Staff' },
  teacher: { email: 'maria.alvarez@demo.test', radio: 'Staff' },
  guardian: { email: 'guardian@demo.test', radio: 'Contact/Guardian' },
} as const;

type Shot = {
  name: string;
  user: keyof typeof USERS | null;
  path: string;
  /** Runs after the page loads, e.g. to open a modal or follow the first row. */
  action?: (page: Page) => Promise<void>;
};

const openFirst = (selector: string) => async (page: Page) => {
  await page.locator(selector).first().click();
  await page.waitForLoadState('networkidle');
};

/** DemoSeeder slots start next weekday at 15:00; the calendar opens on today at 08:00. */
const showSlots = async (page: Page) => {
  await page.getByRole('button', { name: /next/i }).first().click();
  await page.locator('.fc-event').first().scrollIntoViewIfNeeded();
};

/** Names here are what docs pass to <Screenshot name="..." />. */
export const SHOTS: Shot[] = [
  { name: 'login', user: null, path: '/login' },
  { name: 'dashboard-admin', user: 'admin', path: '/' },
  { name: 'dashboard-teacher', user: 'teacher', path: '/' },
  { name: 'dashboard-guardian', user: 'guardian', path: '/' },
  { name: 'select-school', user: 'admin', path: '/select-school' },
  { name: 'time-slots', user: 'admin', path: '/time-slots' },
  { name: 'time-slots-create', user: 'admin', path: '/time-slots/create' },
  { name: 'time-slots-teacher', user: 'teacher', path: '/time-slots', action: showSlots },
  { name: 'batches', user: 'admin', path: '/batches' },
  { name: 'students', user: 'admin', path: '/students' },
  { name: 'student-show', user: 'admin', path: '/students', action: openFirst('a[href^="/students/"]') },
  { name: 'sections', user: 'admin', path: '/sections' },
  { name: 'courses', user: 'admin', path: '/courses' },
  { name: 'teachers', user: 'admin', path: '/teachers' },
  { name: 'users', user: 'admin', path: '/users' },
  { name: 'user-show', user: 'admin', path: '/users', action: async (page) => {
    await openFirst('a[href^="/users/"]')(page);
    await showSlots(page);
  } },
  { name: 'translator-requests', user: 'admin', path: '/translators' },
  { name: 'translator-profiles', user: 'admin', path: '/translator-profiles' },
  { name: 'activity', user: 'admin', path: '/activity' },
  { name: 'settings-tenant', user: 'admin', path: '/settings/tenant/edit' },
  { name: 'settings-school', user: 'admin', path: '/settings/school/edit' },
  { name: 'settings-personal', user: 'guardian', path: '/settings/personal/edit' },
  { name: 'settings-personal-staff', user: 'teacher', path: '/settings/personal/edit' },
];

async function logIn(browser: Browser, user: keyof typeof USERS) {
  const context = await browser.newContext({ ignoreHTTPSErrors: true, baseURL: BASE_URL, timezoneId: TIMEZONE });
  const page = await context.newPage();
  const { email, radio } = USERS[user];

  await page.goto('/login');
  await page.getByRole('radio', { name: radio }).click();
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password').fill(PASSWORD);
  await page.getByRole('button', { name: 'Log in', exact: true }).click();
  await page.waitForURL((url) => !url.pathname.startsWith('/login'));

  const state = await context.storageState();
  await context.close();

  return state;
}

const filter = process.argv[2];
const shots = SHOTS.filter((shot) => !filter || shot.name.includes(filter));
const browser = await chromium.launch();
await mkdir(OUT, { recursive: true });

const sessions = new Map<string, Awaited<ReturnType<typeof logIn>>>();
for (const user of new Set(shots.map((shot) => shot.user).filter((user) => user !== null))) {
  sessions.set(user, await logIn(browser, user));
}

for (const shot of shots) {
  for (const colorScheme of ['light', 'dark'] as const) {
    const context = await browser.newContext({
      ignoreHTTPSErrors: true,
      baseURL: BASE_URL,
      viewport: { width: 1440, height: 900 },
      deviceScaleFactor: 2,
      colorScheme,
      timezoneId: TIMEZONE,
      storageState: shot.user ? sessions.get(shot.user) : undefined,
    });
    const page = await context.newPage();

    try {
      await page.goto(shot.path, { waitUntil: 'networkidle' });
      await shot.action?.(page);
      // Let transitions and fonts settle
      await page.waitForTimeout(500);
      await page.screenshot({ path: new URL(`${shot.name}-${colorScheme}.png`, OUT).pathname });
      console.log(`✓ ${shot.name}-${colorScheme} (${page.url()})`);
    } catch (error) {
      console.error(`✗ ${shot.name}-${colorScheme}: ${(error as Error).message}`);
      process.exitCode = 1;
    } finally {
      await context.close();
    }
  }
}

await browser.close();

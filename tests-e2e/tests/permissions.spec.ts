import { expect, test, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

const protectedRoutes = {
  adminDashboard: '/admin/dashboard',
  userManagement: '/admin/users',
  systemSettings: '/admin/settings/organization',
  accountingSetup: '/admin/accounting/settings',
  invalidProtectedUrl: '/admin/__invalid-protected-url__',
};

const leakPatterns = [
  /APP_KEY=/i,
  /APP_DEBUG=/i,
  /DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=/i,
  /UHMS_[A-Z_]*PASSWORD=/i,
  /SQLSTATE\[/i,
  /QueryException/i,
  /PDOException/i,
  /Whoops/i,
  /Ignition/i,
  /Stack trace/i,
  /Exception trace/i,
  /Symfony\\Component/i,
  /vendor[\\/]+laravel[\\/]+framework/i,
  /app[\\/]+Http[\\/]+Middleware/i,
  /C:\\xampp[\\/]/i,
  /\/var\/www\//i,
  /\.env(?:\s|$|:)/i,
];

test.describe.configure({ timeout: 60_000 });

test.beforeAll(() => {
  ensurePermissionE2EUsers();
});

test.afterAll(() => {
  cleanupPermissionE2EUsers();
});

async function bodyText(page: Page) {
  try {
    return await page.evaluate(() => document.body?.innerText ?? '');
  } catch {
    return '';
  }
}

async function assertNoSensitiveLeak(page: Page) {
  const body = await bodyText(page);

  for (const pattern of leakPatterns) {
    expect(body).not.toMatch(pattern);
  }
}

async function assertProtectedRouteBlocked(page: Page, path: string, normalPagePattern: RegExp) {
  const response = await page.goto(url(path), { waitUntil: 'commit' });
  const finalUrl = page.url();
  const status = response?.status() ?? 0;
  const body = await bodyText(page);

  await assertNoSensitiveLeak(page);

  const redirectedAway = !finalUrl.includes(path.split('?')[0]);
  const accessDenied = /403|forbidden|unauthori[sz]ed|access denied|not authorized|sign in|login/i.test(body);
  const forbiddenStatus = [401, 403, 404].includes(status);
  const pageLoadedNormally = status >= 200 && status < 300 && normalPagePattern.test(body);

  expect(pageLoadedNormally).toBe(false);
  expect(redirectedAway || forbiddenStatus || accessDenied).toBe(true);
}

test.describe('Level 1B permissions and basic security', () => {
  test('L1-PERM-001 - Receptionist can login', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    await expect.poll(() => page.url()).not.toMatch(/\/login$/);
    await expect(page.locator('input[name="email"]')).toHaveCount(0);
  });

  test('L1-PERM-002 - Receptionist cannot access user management directly', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    await assertProtectedRouteBlocked(page, protectedRoutes.userManagement, /user management|create user|users/i);
  });

  test('L1-PERM-003 - Cashier cannot access system settings directly', async ({ page }) => {
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    await assertProtectedRouteBlocked(page, protectedRoutes.systemSettings, /organization settings|system settings|payment methods/i);
  });

  test('L1-PERM-004 - Doctor cannot access accounting setup directly', async ({ page }) => {
    await loginAs(page, 'UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD');

    await assertProtectedRouteBlocked(page, protectedRoutes.accountingSetup, /accounting settings|posting|fiscal/i);
  });

  test('L1-PERM-005 - Limited user cannot access admin dashboard', async ({ page }) => {
    await loginAs(page, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    await assertProtectedRouteBlocked(page, protectedRoutes.adminDashboard, /admin dashboard|total patients|month revenue/i);
  });

  test('OTB-SEC-001 - Invalid protected URL does not expose Laravel debug or secrets', async ({ page }) => {
    await loginAs(page, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    const response = await page.goto(url(protectedRoutes.invalidProtectedUrl), { waitUntil: 'commit' });

    expect([401, 403, 404, 405]).toContain(response?.status());
    await assertNoSensitiveLeak(page);
  });
});

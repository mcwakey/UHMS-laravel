import { expect, test, type Page } from '@playwright/test';

const baseURL = process.env.UHMS_BASE_URL ?? 'http://localhost:8000';
const adminEmail = process.env.UHMS_ADMIN_EMAIL;
const adminPassword = process.env.UHMS_ADMIN_PASSWORD;

const loginPath = '/login';
const dashboardPath = '/admin/dashboard';
const dashboardUrlPattern = /\/admin\/dashboard$/;

function requireAdminCredentials() {
  if (!adminEmail || !adminPassword) {
    throw new Error('UHMS_ADMIN_EMAIL and UHMS_ADMIN_PASSWORD must be set for auth E2E tests.');
  }

  return { adminEmail, adminPassword };
}

function url(path: string) {
  return new URL(path, baseURL).toString();
}

async function gotoLogin(page: Page) {
  await page.goto(url(loginPath), { waitUntil: 'commit' });
  await expect(page.locator('input[name="email"]')).toBeVisible();
}

async function submitLoginForm(page: Page) {
  const loginForm = page.locator('form').filter({ has: page.locator('input[name="email"]') }).first();

  await expect(loginForm).toHaveCount(1);
  await loginForm.evaluate((form) => {
    if (!(form instanceof HTMLFormElement)) {
      throw new Error('Login form was not an HTML form.');
    }

    form.requestSubmit();
  });
}

async function login(page: Page) {
  const credentials = requireAdminCredentials();

  await gotoLogin(page);
  await page.getByRole('textbox', { name: /email address/i }).fill(credentials.adminEmail);
  await page.locator('input[name="password"]').fill(credentials.adminPassword);
  await Promise.all([
    page.waitForURL(dashboardUrlPattern, { waitUntil: 'commit' }),
    submitLoginForm(page),
  ]);
  await expect.poll(() => page.url()).toMatch(dashboardUrlPattern);
}

async function waitForBodyText(page: Page, pattern: RegExp) {
  await expect
    .poll(
      async () => {
        try {
          return await page.evaluate(() => document.body?.innerText ?? '');
        } catch {
          return '';
        }
      },
      { timeout: 30_000 },
    )
    .toMatch(pattern);
}

test.describe('Level 1 authentication', () => {
  test('L1-AUTH-001 - Admin can login', async ({ page }) => {
    await login(page);

    await expect(page).toHaveURL(dashboardUrlPattern);
  });

  test('L1-AUTH-002 - Wrong password is rejected', async ({ page }) => {
    const credentials = requireAdminCredentials();

    await gotoLogin(page);
    await page.getByRole('textbox', { name: /email address/i }).fill(credentials.adminEmail);
    await page.locator('input[name="password"]').fill(`${credentials.adminPassword}-wrong`);
    await submitLoginForm(page);

    await waitForBodyText(page, /provided credentials do not match/i);
  });

  test('L1-AUTH-003 - Dashboard cannot be accessed when logged out', async ({ page }) => {
    await page.goto(url(dashboardPath), { waitUntil: 'commit' });

    await expect.poll(() => page.url()).toMatch(/\/login$/);
    await expect(page.getByRole('button', { name: /^login$/i })).toBeVisible();
  });

  test('L1-AUTH-004 - Logout blocks dashboard access', async ({ page }) => {
    await login(page);
    await waitForBodyText(page, /admin dashboard|dashboard/i);

    const logoutForm = page.locator('form[action$="/logout"]').first();
    await expect(logoutForm).toHaveCount(1, { timeout: 15_000 });

    await logoutForm.evaluate(async (form) => {
      if (!(form instanceof HTMLFormElement)) {
        throw new Error('Logout form was not an HTML form.');
      }

      await fetch(form.action, {
        method: form.method || 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
      });
    });

    await page.goto(url(dashboardPath), { waitUntil: 'commit' });
    await expect.poll(() => page.url()).toMatch(/\/login$/);
  });
});

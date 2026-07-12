import { expect, test } from '@playwright/test';
import { loginAs, requiredCredentials, submitLoginForm, url, waitForBodyText } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

test.beforeAll(() => ensurePermissionE2EUsers());
test.afterAll(() => cleanupPermissionE2EUsers());

const dashboardPath = '/admin/dashboard';
const dashboardUrlPattern = /\/admin\/dashboard$/;

async function loginAsAdmin(page: Parameters<typeof loginAs>[0]) {
  await loginAs(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
  await expect.poll(() => page.url()).toMatch(dashboardUrlPattern);
}

test.describe('Level 1 authentication', () => {
  test('L1-AUTH-001 - Admin can login', async ({ page }) => {
    await loginAsAdmin(page);

    await expect(page).toHaveURL(dashboardUrlPattern);
  });

  test('L1-AUTH-002 - Wrong password is rejected', async ({ page }) => {
    const credentials = requiredCredentials('UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');

    await page.goto(url('/login'), { waitUntil: 'commit' });
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await page.getByRole('textbox', { name: /email address/i }).fill(credentials.email);
    await page.locator('input[name="password"]').fill(`${credentials.password}-wrong`);
    await submitLoginForm(page);

    await waitForBodyText(page, /provided credentials do not match/i);
  });

  test('L1-AUTH-003 - Dashboard cannot be accessed when logged out', async ({ page }) => {
    await page.goto(url(dashboardPath), { waitUntil: 'commit' });

    await expect.poll(() => page.url()).toMatch(/\/login$/);
    await expect(page.getByRole('button', { name: /^login$/i })).toBeVisible();
  });

  test('L1-AUTH-004 - Logout blocks dashboard access', async ({ page }) => {
    await loginAsAdmin(page);

    const cookies = await page.context().cookies(url('/'));
    const xsrfToken = cookies.find((cookie) => cookie.name === 'XSRF-TOKEN')?.value;

    const logoutResponse = await page.request.post(url('/logout'), {
      headers: xsrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {},
    });
    expect(logoutResponse.ok()).toBe(true);

    await page.goto(url(dashboardPath), { waitUntil: 'commit' });
    await expect.poll(() => page.url()).toMatch(/\/login$/);
  });
});

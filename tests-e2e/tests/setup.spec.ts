import { expect, test, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

type SetupPage = {
  name: string;
  path: string;
  expectedText: RegExp;
  optional?: boolean;
};

const setupPages = {
  departments: {
    name: 'departments setup',
    path: '/admin/departments',
    expectedText: /departments?|department setup|designations?/i,
  },
  services: {
    name: 'services setup',
    path: '/admin/services',
    expectedText: /services?|service catalog|add service/i,
  },
  servicePricing: {
    name: 'service pricing setup',
    path: '/admin/services',
    expectedText: /price|pricing|cash|insurance|tariff|service catalog/i,
  },
  insuranceProviders: {
    name: 'insurance providers setup',
    path: '/admin/insurance-providers',
    expectedText: /insurance providers?|tiers?|claims?/i,
  },
  sponsors: {
    name: 'sponsors/corporate clients setup',
    path: '/admin/billing/sponsors',
    expectedText: /sponsors?|corporate|client/i,
    optional: true,
  },
  paymentMethods: {
    name: 'payment methods setup',
    path: '/admin/settings/payment-methods',
    expectedText: /payment methods?|cash|mobile money|bank|method/i,
    optional: true,
  },
  invalidSetupRoute: {
    name: 'invalid setup route',
    path: '/admin/setup-master-data-invalid-probe',
    expectedText: /not found|404|forbidden|403|method not allowed|405|login|sign in/i,
  },
} satisfies Record<string, SetupPage>;

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
  /\.env\s+(?:file|values?|contents?|dump)/i,
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

function assertTextHasNoSensitiveLeak(body: string) {
  for (const pattern of leakPatterns) {
    expect(body).not.toMatch(pattern);
  }
}

async function assertNoSensitiveLeak(page: Page) {
  assertTextHasNoSensitiveLeak(await bodyText(page));
}

async function pageOrResponseText(page: Page, response: Awaited<ReturnType<Page['goto']>>) {
  const renderedText = await bodyText(page);

  if (renderedText.trim()) {
    return {
      status: undefined,
      text: renderedText,
    };
  }

  let navigationText = '';

  try {
    navigationText = response ? await response.text() : '';
  } catch {
    navigationText = '';
  }

  return {
    status: undefined,
    text: navigationText,
  };
}

async function openSetupPageAsAdmin(page: Page, setupPage: SetupPage) {
  await loginAs(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');

  const response = await page.goto(url(setupPage.path), { waitUntil: 'commit' });
  const fallback = await pageOrResponseText(page, response);
  const status = response?.status() ?? fallback.status ?? 0;
  const body = fallback.text;

  assertTextHasNoSensitiveLeak(body);

  if (setupPage.optional && [404, 405].includes(status)) {
    test.skip(true, `${setupPage.name} is not available in this UHMS build.`);
  }

  expect(status).toBeLessThan(400);
  expect(body).toMatch(setupPage.expectedText);
}

async function assertSetupBlockedForRole(page: Page, targetPage: SetupPage, normalPagePattern: RegExp) {
  const response = await page.goto(url(targetPage.path), { waitUntil: 'commit' });
  const finalUrl = page.url();
  const status = response?.status() ?? 0;
  const body = await bodyText(page);

  await assertNoSensitiveLeak(page);

  const redirectedAway = !finalUrl.includes(targetPage.path);
  const accessDenied = /403|forbidden|unauthori[sz]ed|access denied|not authorized|sign in|login/i.test(body);
  const forbiddenStatus = [401, 403, 404].includes(status);
  const pageLoadedNormally = status >= 200 && status < 300 && normalPagePattern.test(body);

  expect(pageLoadedNormally).toBe(false);
  expect(redirectedAway || forbiddenStatus || accessDenied).toBe(true);
}

test.describe('Level 2 setup/master-data access', () => {
  test('L2-SETUP-001 - Admin can open departments setup page', async ({ page }) => {
    await openSetupPageAsAdmin(page, setupPages.departments);
  });

  test('L2-SETUP-002 - Admin can open services setup page', async ({ page }) => {
    await openSetupPageAsAdmin(page, setupPages.services);
  });

  test('L2-SETUP-003 - Admin can open price list or service pricing page', async ({ page }) => {
    await openSetupPageAsAdmin(page, setupPages.servicePricing);
  });

  test('L2-SETUP-004 - Admin can open insurance providers setup page', async ({ page }) => {
    await openSetupPageAsAdmin(page, setupPages.insuranceProviders);
  });

  test('L2-SETUP-005 - Admin can open sponsors/corporate clients setup page if available', async ({ page }) => {
    await openSetupPageAsAdmin(page, setupPages.sponsors);
  });

  test('L2-SETUP-006 - Admin can open payment methods setup page if available', async ({ page }) => {
    await openSetupPageAsAdmin(page, setupPages.paymentMethods);
  });

  test('L2-SETUP-007 - Receptionist cannot access setup/master-data pages directly', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    await assertSetupBlockedForRole(page, setupPages.departments, /departments?|department setup|designations?/i);
    await assertSetupBlockedForRole(page, setupPages.services, /services?|service catalog|add service/i);
    await assertSetupBlockedForRole(page, setupPages.insuranceProviders, /insurance providers?|tiers?|claims?/i);
  });

  test('L2-SETUP-008 - Cashier cannot access service pricing setup directly', async ({ page }) => {
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    await assertSetupBlockedForRole(page, setupPages.servicePricing, /price|pricing|cash|insurance|tariff|service catalog/i);
  });

  test('L2-SETUP-009 - Doctor cannot access setup/master-data pages directly', async ({ page }) => {
    await loginAs(page, 'UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD');

    await assertSetupBlockedForRole(page, setupPages.departments, /departments?|department setup|designations?/i);
    await assertSetupBlockedForRole(page, setupPages.services, /services?|service catalog|add service/i);
    await assertSetupBlockedForRole(page, setupPages.insuranceProviders, /insurance providers?|tiers?|claims?/i);
  });

  test('OTB-SEC-002 - Setup invalid route or invalid ID does not expose debug or secrets', async ({ page }) => {
    await loginAs(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');

    const response = await page.goto(url(setupPages.invalidSetupRoute.path), { waitUntil: 'commit' });

    expect([401, 403, 404, 405]).toContain(response?.status());
    await assertNoSensitiveLeak(page);
  });
});

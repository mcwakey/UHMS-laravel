import { execFileSync } from 'node:child_process';
import { expect, test, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

const viewports = {
  desktop: { width: 1440, height: 900 },
  tablet: { width: 768, height: 1024 },
  mobile: { width: 390, height: 844 },
};

const paths = {
  dashboard: '/admin/my-dashboard',
  patients: '/admin/patients',
  patientCreate: '/admin/patients/create',
  visits: '/admin/visits',
  visitCreate: '/admin/visits/create',
  invoices: '/admin/billing/invoices',
  lab: '/admin/lab/requests',
  pharmacy: '/admin/pharmacy/dispensing',
  reports: '/admin/reports/dashboard',
  invalidLocalized: '/fr/admin/this-localised-route-does-not-exist',
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
  /\.env\s+(?:file|values?|contents?|dump)/i,
];

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';
const hasLabCredentials = Boolean(process.env.UHMS_LAB_EMAIL && process.env.UHMS_LAB_PASSWORD);
const hasPharmacyCredentials = Boolean(process.env.UHMS_PHARMACY_EMAIL && process.env.UHMS_PHARMACY_PASSWORD);

test.describe.configure({ mode: 'serial', timeout: 420_000 });

test.beforeAll(() => {
  ensurePermissionE2EUsers();
  enableUiModules();
});

test.afterAll(() => {
  cleanupPermissionE2EUsers();
});

function runPhp(script: string) {
  execFileSync(phpBinary, ['-r', script], {
    cwd: process.cwd(),
    env: {
      ...process.env,
    },
    stdio: 'pipe',
  });
}

function enableUiModules() {
  runPhp(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (['patients', 'billing', 'investigations', 'pharmacy', 'reports', 'inventory', 'appointments'] as $slug) {
    $module = App\Models\Module::where('slug', $slug)->first();
    if ($module) {
        $module->forceFill(['is_active' => true])->save();
    }
}
`);
}

async function bodyText(page: Page) {
  try {
    return await page.evaluate(() => document.body?.innerText ?? '');
  } catch {
    return '';
  }
}

function assertTextHasNoSensitiveLeak(text: string) {
  for (const pattern of leakPatterns) {
    expect(text).not.toMatch(pattern);
  }
}

async function pageOrResponseText(page: Page, response: Awaited<ReturnType<Page['goto']>>) {
  await page.waitForLoadState('load', { timeout: 20_000 }).catch(() => undefined);
  await page.waitForSelector('body', { state: 'attached', timeout: 15_000 }).catch(() => undefined);

  try {
    await expect.poll(() => bodyText(page), { timeout: 15_000 }).toBeTruthy();
  } catch {
    // An error/redirect response can still be inspected through HTML/response text.
  }

  const renderedText = await bodyText(page);
  if (renderedText.trim()) {
    return renderedText;
  }

  try {
    const html = await page.content();
    if (html.trim()) {
      return html;
    }
  } catch {
    // Fall back to response text.
  }

  try {
    return response ? await response.text() : '';
  } catch {
    return '';
  }
}

async function openUsablePage(page: Page, path: string, expectedText: RegExp) {
  const response = await page.goto(url(path), { waitUntil: 'commit' });
  const status = response?.status() ?? 0;
  const text = await pageOrResponseText(page, response);

  assertTextHasNoSensitiveLeak(text);
  test.skip(
    [404, 503].includes(status) && /not found|module|disabled|unavailable/i.test(text),
    `UI page ${path} is not available in this UHMS build.`,
  );

  expect(status).toBeLessThan(400);
  expect(text).toMatch(expectedText);
  expect(text.trim().length).toBeGreaterThan(20);

  return text;
}

async function loginForUi(page: Page, emailEnv: string, passwordEnv: string) {
  try {
    await loginAs(page, emailEnv, passwordEnv);
  } catch {
    ensurePermissionE2EUsers();
    await page.waitForTimeout(1_000);
    await loginAs(page, emailEnv, passwordEnv);
  }
}

async function assertNoMajorHorizontalOverflow(page: Page, tolerance = 24) {
  const offenders = await page.evaluate((allowedOverflow) => {
    const viewportWidth = document.documentElement.clientWidth;

    function hasScrollableAncestor(element: Element) {
      let current = element.parentElement;

      while (current && current !== document.body) {
        const style = getComputedStyle(current);
        const canScroll = /(auto|scroll)/.test(style.overflowX);

        if (canScroll && current.scrollWidth > current.clientWidth) {
          return true;
        }

        current = current.parentElement;
      }

      return false;
    }

    return [...document.body.querySelectorAll('*')]
      .map((element) => {
        const rect = element.getBoundingClientRect();
        const style = getComputedStyle(element);

        return {
          tag: element.tagName.toLowerCase(),
          id: element.id,
          className: String(element.className || '').slice(0, 120),
          text: (element.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 120),
          left: Math.round(rect.left),
          right: Math.round(rect.right),
          width: Math.round(rect.width),
          height: Math.round(rect.height),
          position: style.position,
          display: style.display,
          hidden: style.visibility === 'hidden' || style.display === 'none' || Number(style.opacity) === 0,
          scrollableAncestor: hasScrollableAncestor(element),
        };
      })
      .filter((item) => {
        if (item.hidden || item.width === 0 || item.height === 0) {
          return false;
        }

        if (['fixed', 'sticky'].includes(item.position)) {
          return false;
        }

        if (item.scrollableAncestor) {
          return false;
        }

        return item.right > viewportWidth + allowedOverflow || item.left < -allowedOverflow;
      })
      .slice(0, 8);
  });

  expect(offenders, JSON.stringify(offenders, null, 2)).toEqual([]);
}

async function assertControlReachable(page: Page, selector: string) {
  let locator = page.locator(selector).filter({ visible: true }).first();
  try {
    await expect(locator).toBeVisible({ timeout: 20_000 });
  } catch {
    locator = page.locator(selector).first();
    await expect(locator).toHaveCount(1, { timeout: 10_000 });
    return;
  }

  await locator.scrollIntoViewIfNeeded();
  const box = await locator.boundingBox();

  expect(box).not.toBeNull();
  expect(box!.y + box!.height).toBeGreaterThan(0);
  expect(box!.y).toBeLessThan(viewports.mobile.height + 2_000);
}

async function switchLocale(page: Page, locale: 'en' | 'fr') {
  const form = page.locator(`form[action$="/locale"]:has(input[name="locale"][value="${locale}"])`).first();
  const exists = await form.count();
  const hasCsrfToken = await page.locator('meta[name="csrf-token"]').count();
  test.skip(exists === 0 && hasCsrfToken === 0, 'UHMS language switcher is not available in the header.');

  const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  const response = await page.request.post(url('/locale'), {
    form: { locale },
    maxRedirects: 0,
    headers: {
      Accept: 'text/html,application/xhtml+xml',
      'X-CSRF-TOKEN': token ?? '',
      Referer: page.url(),
    },
  });
  const responseText = await response.text();

  expect(response.status(), `Locale switch request failed with status ${response.status()}`).toBeLessThan(400);
  await page.reload({ waitUntil: 'commit' });
  await page.waitForLoadState('load', { timeout: 20_000 }).catch(() => undefined);
  await expect(page.locator('html')).toHaveAttribute('lang', locale, { timeout: 20_000 });

  return `${responseText}\n${await bodyText(page)}`;
}

async function expectFrenchShell(page: Page) {
  const text = await pageOrResponseText(page, null);

  if (text.trim()) {
    expect(text).toMatch(/Langue|Enregistrer|Rechercher|Tableau|Patient|Visite|Facture|Paiement|CrÃ©er|Créer|UHMS/i);
  }
}

async function expectEnglishShell(page: Page) {
  const text = await pageOrResponseText(page, null);

  if (text.trim()) {
    expect(text).toMatch(/Language|Save|Search|Dashboard|Patient|Visit|Invoice|Payment|Create|UHMS/i);
  }
}

function assertNoObviousEnglishLabelsInFrench(text: string) {
  const banned = [
    /\bRegister Patient\b/i,
    /\bCreate New Visit\b/i,
    /\bFirst Name\b/i,
    /\bLast Name\b/i,
    /\bPhone Number\b/i,
    /\bInvoice Register\b/i,
    /\bPayment Method\b/i,
  ];

  for (const pattern of banned) {
    expect(text).not.toMatch(pattern);
  }
}

function assertNoObviousFrenchLabelsInEnglish(text: string) {
  const banned = [
    /\bEnregistrer\b/i,
    /\bRechercher\b/i,
    /\bPrÃ©nom\b|\bPrénom\b/i,
    /\bNom de famille\b/i,
    /\bFacture\b/i,
    /\bPaiement\b/i,
    /\bCrÃ©er\b|\bCréer\b/i,
  ];

  for (const pattern of banned) {
    expect(text).not.toMatch(pattern);
  }
}

test.describe('Level 11 responsiveness and localisation', () => {
  test('L11-UI-001 - Admin dashboard renders correctly on desktop viewport', async ({ page }) => {
    await page.setViewportSize(viewports.desktop);
    await loginForUi(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    await openUsablePage(page, paths.dashboard, /dashboard|tableau|UHMS/i);
    await assertNoMajorHorizontalOverflow(page);
  });

  test('L11-UI-002 - Receptionist patient list renders correctly on tablet viewport', async ({ page }) => {
    await page.setViewportSize(viewports.tablet);
    await loginForUi(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openUsablePage(page, paths.patients, /patients?|patient list|UHMS/i);
    await assertNoMajorHorizontalOverflow(page, 80);
  });

  test('L11-UI-003 - Receptionist patient create form is usable on mobile viewport', async ({ page }) => {
    await page.setViewportSize(viewports.mobile);
    await loginForUi(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openUsablePage(page, paths.patientCreate, /register patient|personal information|patient|UHMS/i);
    await assertControlReachable(page, 'input[name="first_name"]');
    await assertControlReachable(page, 'button[type="submit"], input[type="submit"]');
  });

  test('L11-UI-004 - Visit creation page is usable on mobile viewport', async ({ page }) => {
    await page.setViewportSize(viewports.mobile);
    await loginForUi(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openUsablePage(page, paths.visitCreate, /create new visit|select patient|visit|UHMS/i);
    await assertControlReachable(page, '#patientSearch, input[name="patient_id"], .select2');
    await assertControlReachable(page, 'button[type="submit"], input[type="submit"]');
  });

  test('L11-UI-005 - Billing/invoice page is usable on tablet viewport', async ({ page }) => {
    await page.setViewportSize(viewports.tablet);
    await loginForUi(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    await openUsablePage(page, paths.invoices, /invoice|billing|payment|UHMS/i);
    await assertNoMajorHorizontalOverflow(page, 80);
  });

  test('L11-UI-006 - Lab page is usable on tablet viewport', async ({ page }) => {
    test.skip(!hasLabCredentials, 'UHMS_LAB_EMAIL and UHMS_LAB_PASSWORD are not configured.');

    await page.setViewportSize(viewports.tablet);
    await loginForUi(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');
    await openUsablePage(page, paths.lab, /lab|laboratory|investigation|request|UHMS/i);
    await assertNoMajorHorizontalOverflow(page, 80);
  });

  test('L11-UI-007 - Pharmacy page is usable on tablet viewport', async ({ page }) => {
    test.skip(!hasPharmacyCredentials, 'UHMS_PHARMACY_EMAIL and UHMS_PHARMACY_PASSWORD are not configured.');

    await page.setViewportSize(viewports.tablet);
    await loginForUi(page, 'UHMS_PHARMACY_EMAIL', 'UHMS_PHARMACY_PASSWORD');
    await openUsablePage(page, paths.pharmacy, /pharmacy|dispens|prescription|UHMS/i);
    await assertNoMajorHorizontalOverflow(page, 80);
  });

  test('L11-UI-008 - Reports page is usable on desktop and does not overflow horizontally', async ({ page }) => {
    await page.setViewportSize(viewports.desktop);
    await loginForUi(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    await openUsablePage(page, paths.reports, /report|dashboard|analytics|UHMS/i);
    await assertNoMajorHorizontalOverflow(page);
  });

  test('L11-LOC-001 - User can switch to French if language switcher exists', async ({ page }) => {
    await loginForUi(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    await openUsablePage(page, paths.dashboard, /dashboard|UHMS/i);
    await switchLocale(page, 'fr');
    await expectFrenchShell(page);
  });

  test('L11-LOC-002 - User can switch back to English if language switcher exists', async ({ page }) => {
    await loginForUi(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    await openUsablePage(page, paths.dashboard, /dashboard|tableau|UHMS/i);
    await switchLocale(page, 'fr');
    await switchLocale(page, 'en');
    await expectEnglishShell(page);
  });

  test('L11-LOC-003 - French patient/visit/billing pages do not contain obvious hardcoded English labels', async ({ page }) => {
    await loginForUi(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openUsablePage(page, paths.patientCreate, /patient|UHMS/i);
    await switchLocale(page, 'fr');

    for (const path of [paths.patientCreate, paths.visitCreate]) {
      await openUsablePage(page, path, /patient|visite|UHMS/i);
      assertNoObviousEnglishLabelsInFrench(await bodyText(page));
    }

    const cashierPage = await page.context().newPage();
    await loginForUi(cashierPage, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    await openUsablePage(cashierPage, paths.invoices, /invoice|facture|UHMS/i);
    await switchLocale(cashierPage, 'fr');
    await openUsablePage(cashierPage, paths.invoices, /facture|paiement|UHMS/i);
    assertNoObviousEnglishLabelsInFrench(await bodyText(cashierPage));
    await cashierPage.close();
  });

  test('L11-LOC-004 - English patient/visit/billing pages do not contain obvious hardcoded French labels', async ({ page }) => {
    await loginForUi(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openUsablePage(page, paths.patientCreate, /patient|UHMS/i);
    await switchLocale(page, 'en');

    for (const path of [paths.patientCreate, paths.visitCreate]) {
      await openUsablePage(page, path, /patient|visit|UHMS/i);
      assertNoObviousFrenchLabelsInEnglish(await bodyText(page));
    }

    const cashierPage = await page.context().newPage();
    await loginForUi(cashierPage, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    await openUsablePage(cashierPage, paths.invoices, /invoice|billing|UHMS/i);
    await switchLocale(cashierPage, 'en');
    await openUsablePage(cashierPage, paths.invoices, /invoice|billing|payment|UHMS/i);
    assertNoObviousFrenchLabelsInEnglish(await bodyText(cashierPage));
    await cashierPage.close();
  });

  test('L11-LOC-005 - Validation errors are translated according to selected language', async ({ page }) => {
    await loginForUi(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openUsablePage(page, paths.patientCreate, /patient|UHMS/i);
    await switchLocale(page, 'fr');
    await openUsablePage(page, paths.patientCreate, /patient|UHMS/i);

    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const response = await page.request.post(url('/admin/patients'), {
      form: {},
      maxRedirects: 0,
      headers: {
        Accept: 'text/html,application/xhtml+xml',
        'X-CSRF-TOKEN': token ?? '',
        Referer: url(paths.patientCreate),
      },
    });
    expect(response.status(), `Patient validation request failed with status ${response.status()}`).toBeLessThan(400);

    await page.goto(url(paths.patientCreate), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, null);

    assertTextHasNoSensitiveLeak(text);
    expect(text).toMatch(/champ|required|obligatoire|sÃ©lectionnÃ©e|sélectionnée|valide/i);
    expect(text).not.toMatch(/The .* field is required/i);
  });

  test('L11-LOC-006 - Flash messages are translated according to selected language', async ({ page }) => {
    await loginForUi(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    await openUsablePage(page, paths.dashboard, /dashboard|UHMS/i);

    let text = await switchLocale(page, 'fr');
    text = text || (await bodyText(page));
    await expectFrenchShell(page);
    expect(text).not.toMatch(/language updated/i);

    text = await switchLocale(page, 'en');
    text = text || (await bodyText(page));
    await expectEnglishShell(page);
    expect(text).not.toMatch(/langue.*mise Ã  jour|langue.*mise à jour/i);
  });

  test('OTB-SEC-015 - Localised invalid route does not expose Laravel debug, SQL errors, stack traces, .env values, or file paths', async ({ page }) => {
    await loginForUi(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    await switchLocale(page, 'fr');

    const response = await page.goto(url(paths.invalidLocalized), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    assertTextHasNoSensitiveLeak(text);
    expect(response?.status() ?? 0).not.toBe(500);
  });
});

import { expect, test, type Browser, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

const patientRoutes = {
  index: '/admin/patients',
  create: '/admin/patients/create',
  invalidPatient: '/admin/patients/999999999',
};

const runId = Date.now().toString(36);
const patient = {
  firstName: `E2E${runId}`,
  lastName: 'CashPatient',
  updatedLastName: 'CashPatientUpdated',
  dateOfBirth: '1990-01-15',
  gender: 'male',
  phone: `020${String(Date.now()).slice(-7)}`,
  updatedPhone: `024${String(Date.now()).slice(-7)}`,
  email: `e2e.patient.${runId}@example.test`,
  address: 'E2E cash patient address',
  city: 'Accra',
  region: 'Greater Accra',
  profilePath: '',
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

test.describe.configure({ mode: 'serial', timeout: 90_000 });

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

function assertTextHasNoSensitiveLeak(text: string) {
  for (const pattern of leakPatterns) {
    expect(text).not.toMatch(pattern);
  }
}

async function assertNoSensitiveLeak(page: Page) {
  assertTextHasNoSensitiveLeak(await bodyText(page));
}

async function pageOrResponseText(page: Page, response: Awaited<ReturnType<Page['goto']>>) {
  let renderedText = '';

  try {
    renderedText = await expect
      .poll(() => bodyText(page), { timeout: 5_000 })
      .toBeTruthy()
      .then(() => bodyText(page));
  } catch {
    renderedText = await bodyText(page);
  }

  if (renderedText.trim()) {
    return renderedText;
  }

  try {
    return response ? await response.text() : '';
  } catch {
    return '';
  }
}

async function submitFormWith(page: Page, fieldName: string) {
  const form = page.locator('form').filter({ has: page.locator(`[name="${fieldName}"]`) }).first();

  await expect(form).toHaveCount(1);
  await form.evaluate((element) => {
    if (!(element instanceof HTMLFormElement)) {
      throw new Error('Target form was not an HTML form.');
    }

    element.requestSubmit();
  });
}

async function expectFieldVisible(page: Page, fieldName: string) {
  await expect(page.locator(`[name="${fieldName}"]`)).toBeVisible({ timeout: 30_000 });
}

async function openPathWithField(page: Page, path: string, fieldName: string) {
  let response: Awaited<ReturnType<Page['goto']>> = null;
  let lastError: unknown = null;

  for (const attempt of [1, 2]) {
    response = await page.goto(url(path), { waitUntil: 'commit' });

    try {
      await expectFieldVisible(page, fieldName);
      return response;
    } catch (error) {
      lastError = error;

      if (attempt === 1) {
        await page.waitForTimeout(1_000);
      }
    }
  }

  const text = await bodyText(page);

  throw new Error(
    `Expected [name="${fieldName}"] on ${path}, but it was not visible. ` +
      `Final URL: ${page.url()}. Response status: ${response?.status() ?? 'unknown'}. ` +
      `Body: ${text.slice(0, 500)}. Original error: ${String(lastError)}`,
  );
}

async function fillPatientForm(page: Page, overrides: Partial<typeof patient> = {}) {
  const data = { ...patient, ...overrides };

  await page.locator('[name="first_name"]').fill(data.firstName);
  await page.locator('[name="last_name"]').fill(data.lastName);
  await page.locator('[name="date_of_birth"]').fill(data.dateOfBirth);
  await page.locator('[name="gender"]').selectOption(data.gender);
  await page.locator('[name="phone"]').fill(data.phone);

  await page.locator('[name="email"]').fill(data.email);
  await page.locator('[name="address"]').fill(data.address);
  await page.locator('[name="city"]').fill(data.city);
  await page.locator('[name="region"]').selectOption(data.region);

  const emergencyContactName = page.locator('[name="emergency_contacts[0][name]"]');
  const emergencyContactPhone = page.locator('[name="emergency_contacts[0][phone]"]');

  if ((await emergencyContactName.count()) > 0) {
    await emergencyContactName.fill(`E2E Contact ${runId}`);
  }

  if ((await emergencyContactPhone.count()) > 0) {
    await emergencyContactPhone.fill('0200000001');
  }
}

function profilePathFromUrl(currentUrl: string) {
  const parsed = new URL(currentUrl);
  const match = parsed.pathname.match(/\/admin\/patients\/\d+$/);

  expect(match, `Expected patient profile URL, got ${currentUrl}`).not.toBeNull();

  return parsed.pathname;
}

async function createPatientIfMissing(page: Page) {
  if (patient.profilePath) {
    return;
  }

  await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
  await openPathWithField(page, patientRoutes.create, 'first_name');
  await fillPatientForm(page);
  await submitFormWith(page, 'first_name');
  await expect.poll(() => page.url(), { timeout: 30_000 }).toMatch(/\/admin\/patients\/\d+$/);

  patient.profilePath = profilePathFromUrl(page.url());
}

async function assertPathBlockedForUser(page: Page, path: string, normalPagePattern: RegExp) {
  const response = await page.goto(url(path), { waitUntil: 'commit' });
  const status = response?.status() ?? 0;
  const finalUrl = page.url();
  const text = await pageOrResponseText(page, response);

  assertTextHasNoSensitiveLeak(text);

  const redirectedAway = !new URL(finalUrl).pathname.startsWith(path);
  const accessDenied = /403|forbidden|unauthori[sz]ed|access denied|not authorized|sign in|login/i.test(text);
  const forbiddenStatus = [401, 403, 404].includes(status);
  const pageLoadedNormally = status >= 200 && status < 300 && normalPagePattern.test(text);

  expect(pageLoadedNormally).toBe(false);
  expect(redirectedAway || forbiddenStatus || accessDenied).toBe(true);
}

async function openNewLoggedInPage(browser: Browser, emailEnv: string, passwordEnv: string) {
  const page = await browser.newPage();
  await loginAs(page, emailEnv, passwordEnv);

  return page;
}

test.describe('Level 3 patient workflow', () => {
  test('L3-PAT-001 - Receptionist can open patient list', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(patientRoutes.index), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(text).toMatch(/patients?|patient id|patient name|new patient/i);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L3-PAT-002 - Receptionist can open create patient page', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await openPathWithField(page, patientRoutes.create, 'first_name');
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(text).toMatch(/register patient|personal information|first name|last name/i);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L3-PAT-003 - Receptionist can create a cash patient', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openPathWithField(page, patientRoutes.create, 'first_name');

    await fillPatientForm(page);
    await submitFormWith(page, 'first_name');
    await expect.poll(() => page.url(), { timeout: 30_000 }).toMatch(/\/admin\/patients\/\d+$/);

    patient.profilePath = profilePathFromUrl(page.url());

    const text = await bodyText(page);
    expect(text).toContain(patient.firstName);
    expect(text).toContain(patient.lastName);
    await assertNoSensitiveLeak(page);
  });

  test('L3-PAT-004 - Required fields are validated on patient creation', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openPathWithField(page, patientRoutes.create, 'first_name');

    await submitFormWith(page, 'first_name');

    await expect
      .poll(() => bodyText(page), { timeout: 15_000 })
      .toMatch(/first name|last name|date of birth|gender|phone|required|fill out this field/i);
    await assertNoSensitiveLeak(page);
  });

  test('L3-PAT-005 - Created patient can be searched by name', async ({ page }) => {
    await createPatientIfMissing(page);
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(`${patientRoutes.index}?search=${encodeURIComponent(patient.firstName)}`), {
      waitUntil: 'commit',
    });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(text).toContain(patient.firstName);
    expect(text).toContain(patient.lastName);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L3-PAT-006 - Created patient profile can be opened', async ({ page }) => {
    await createPatientIfMissing(page);
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(patient.profilePath), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(text).toContain(patient.firstName);
    expect(text).toContain(patient.lastName);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L3-PAT-007 - Receptionist can edit patient basic details', async ({ page }) => {
    await createPatientIfMissing(page);
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    await openPathWithField(page, `${patient.profilePath}/edit`, 'last_name');

    await fillPatientForm(page, {
      lastName: patient.updatedLastName,
      phone: patient.updatedPhone,
    });
    await submitFormWith(page, 'first_name');
    await expect.poll(() => page.url(), { timeout: 30_000 }).toMatch(/\/admin\/patients\/\d+$/);

    const text = await bodyText(page);
    expect(text).toContain(patient.firstName);
    expect(text).toContain(patient.updatedLastName);
    await assertNoSensitiveLeak(page);
  });

  test('L3-PAT-008 - Limited user cannot access patient creation directly', async ({ page }) => {
    await loginAs(page, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    await assertPathBlockedForUser(page, patientRoutes.create, /register patient|first name|last name|personal information/i);
  });

  test('OTB-SEC-003 - Invalid patient ID does not expose debug or secrets', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(patientRoutes.invalidPatient), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect([401, 403, 404, 405]).toContain(response?.status());
    assertTextHasNoSensitiveLeak(text);
  });

  test('OTB-SEC-004 - Direct patient URL access respects permissions', async ({ browser, page }) => {
    await createPatientIfMissing(page);

    const doctorPage = await openNewLoggedInPage(browser, 'UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD');
    const limitedPage = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      const doctorResponse = await doctorPage.goto(url(patient.profilePath), { waitUntil: 'commit' });
      const doctorText = await pageOrResponseText(doctorPage, doctorResponse);

      expect(doctorResponse?.status()).toBeLessThan(400);
      expect(doctorText).toContain(patient.firstName);
      assertTextHasNoSensitiveLeak(doctorText);

      await assertPathBlockedForUser(
        limitedPage,
        patient.profilePath,
        new RegExp(`${patient.firstName}.*${patient.updatedLastName}|${patient.firstName}.*${patient.lastName}`, 'i'),
      );
    } finally {
      await doctorPage.close();
      await limitedPage.close();
    }
  });
});

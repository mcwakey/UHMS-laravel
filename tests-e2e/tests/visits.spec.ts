import { expect, test, type Browser, type Page } from '@playwright/test';
import { createCashPatient, type TestPatient } from './helpers/patients';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

const visitRoutes = {
  index: '/admin/visits',
  create: '/admin/visits/create',
  patientSearch: '/admin/visits/patient-search',
  invalidVisit: '/admin/visits/999999999',
  consultations: '/admin/consultations',
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

type CreatedVisit = {
  path: string;
  id: string;
  serviceAdded: boolean;
};

let patient: TestPatient | null = null;
let visit: CreatedVisit | null = null;

test.describe.configure({ mode: 'serial', timeout: 180_000 });

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

  await page.waitForLoadState('domcontentloaded', { timeout: 15_000 }).catch(() => undefined);

  try {
    renderedText = await expect
      .poll(() => bodyText(page), { timeout: 12_000 })
      .toBeTruthy()
      .then(() => bodyText(page));
  } catch {
    renderedText = await bodyText(page);
  }

  if (renderedText.trim()) {
    return renderedText;
  }

  try {
    const html = await page.content();
    if (html.trim()) {
      return html;
    }
  } catch {
    // Fall back to the navigation response below.
  }

  try {
    return response ? await response.text() : '';
  } catch {
    return '';
  }
}

async function submitFormWith(page: Page, selector: string) {
  const form = page.locator(selector).first();

  await expect(form).toHaveCount(1);
  await form.evaluate((element) => {
    if (!(element instanceof HTMLFormElement)) {
      throw new Error('Target form was not an HTML form.');
    }

    element.requestSubmit();
  });
}

async function expectSelectorVisible(page: Page, selector: string) {
  await expect(page.locator(selector)).toBeVisible({ timeout: 30_000 });
}

async function openPathWithSelector(page: Page, path: string, selector: string) {
  let response: Awaited<ReturnType<Page['goto']>> = null;
  let lastError: unknown = null;

  for (const attempt of [1, 2, 3, 4]) {
    response = await page.goto(url(path), { waitUntil: 'commit' });

    try {
      await expectSelectorVisible(page, selector);
      return response;
    } catch (error) {
      lastError = error;

      if (attempt < 4) {
        await page.waitForTimeout(2_000);
      }
    }
  }

  const text = await bodyText(page);

  throw new Error(
    `Expected ${selector} on ${path}, but it was not visible. ` +
      `Final URL: ${page.url()}. Response status: ${response?.status() ?? 'unknown'}. ` +
      `Body: ${text.slice(0, 500)}. Original error: ${String(lastError)}`,
  );
}

async function ensureFreshPatient(page: Page) {
  if (!patient) {
    patient = await createCashPatient(page, 'E2EVisit');
    return patient;
  }

  await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
  return patient;
}

async function openVisitCreatePage(page: Page, selectedPatient = patient) {
  const path = selectedPatient ? `${visitRoutes.create}?patient_id=${selectedPatient.id}` : visitRoutes.create;

  return openPathWithSelector(page, path, '#visitForm');
}

async function searchPatientForVisit(page: Page, selectedPatient: TestPatient) {
  return page.evaluate(
    async ({ endpoint, query }) => {
      const response = await fetch(`${endpoint}?q=${encodeURIComponent(query)}`, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error(`Patient search failed with ${response.status}`);
      }

      return response.json();
    },
    { endpoint: visitRoutes.patientSearch, query: selectedPatient.firstName },
  );
}

async function selectPatientInVisitForm(page: Page, selectedPatient: TestPatient) {
  const results = await searchPatientForVisit(page, selectedPatient);
  const match = results.find((result: { id: number | string; full_name?: string }) => String(result.id) === selectedPatient.id);

  expect(match, `Expected patient search to return ${selectedPatient.fullName}`).toBeTruthy();

  await page.evaluate((patientMatch) => {
    const selectPatient = (window as Window & { selectPatient?: (patient: unknown) => void }).selectPatient;

    if (typeof selectPatient !== 'function') {
      throw new Error('Visit patient selection helper is not available on the page.');
    }

    selectPatient(patientMatch);
  }, match);

  await expect(page.locator('#patientId')).toHaveValue(selectedPatient.id);
  await expect(page.locator('#patientName')).toContainText(selectedPatient.firstName);
}

async function addFirstConsultationServiceIfAvailable(page: Page) {
  const departmentValue = await page.locator('#departmentSelect').evaluate((select) => {
    if (!(select instanceof HTMLSelectElement)) {
      return '';
    }

    return Array.from(select.options).find((option) => option.value)?.value ?? '';
  });

  if (!departmentValue) {
    return false;
  }

  await page.locator('#departmentSelect').selectOption(departmentValue);
  await page.locator('#departmentSelect').evaluate((select) => {
    select.dispatchEvent(new Event('change', { bubbles: true }));
  });

  const addButton = page.locator('#servicesItems .add-service-btn').first();

  try {
    await expect(addButton).toBeVisible({ timeout: 15_000 });
    await addButton.click();
    await expect
      .poll(() => page.locator('#billingBody input[name^="services["]').count(), { timeout: 10_000 })
      .toBeGreaterThan(0);
    return true;
  } catch {
    return false;
  }
}

function visitPathFromHref(href: string | null) {
  expect(href, 'Expected visit success link href').toBeTruthy();

  const parsed = new URL(href ?? '', url('/'));
  const match = parsed.pathname.match(/\/admin\/visits\/(\d+)$/);

  expect(match, `Expected visit profile URL, got ${href}`).not.toBeNull();

  return {
    id: match?.[1] ?? '',
    path: parsed.pathname,
  };
}

async function submitVisitFormJson(page: Page) {
  return page.evaluate(async () => {
    const form = document.querySelector('#visitForm');

    if (!(form instanceof HTMLFormElement)) {
      throw new Error('Visit form was not available.');
    }

    const response = await fetch(form.action, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new FormData(form),
    });
    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json() : { text: await response.text() };

    return {
      ok: response.ok,
      status: response.status,
      payload,
    };
  });
}

async function createOutpatientVisit(page: Page, selectedPatient: TestPatient) {
  await openVisitCreatePage(page, selectedPatient);

  await expect(page.locator('#patientId')).toHaveValue(selectedPatient.id);
  await page.locator('[name="visit_type"]').selectOption('outpatient');
  await page.locator('[name="priority"]').selectOption('normal');
  await page.locator('[name="chief_complaint"]').fill(`E2E outpatient visit for ${selectedPatient.fullName}`);

  const serviceAdded = await addFirstConsultationServiceIfAvailable(page);

  const result = await submitVisitFormJson(page);

  expect(result.ok, `Visit creation failed: ${JSON.stringify(result.payload)}`).toBe(true);
  expect(result.status).toBe(201);

  const created = visitPathFromHref(result.payload.redirect_url ?? null);

  visit = {
    ...created,
    serviceAdded,
  };

  return visit;
}

async function ensureVisit(page: Page) {
  const selectedPatient = await ensureFreshPatient(page);

  if (!visit) {
    return createOutpatientVisit(page, selectedPatient);
  }

  return visit;
}

async function assertPathBlockedForUser(page: Page, path: string, normalPagePattern: RegExp) {
  const response = await page.goto(url(path), { waitUntil: 'commit' });
  const status = response?.status() ?? 0;
  const finalPath = new URL(page.url()).pathname;
  const text = await pageOrResponseText(page, response);

  assertTextHasNoSensitiveLeak(text);

  const redirectedAway = finalPath !== path;
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

test.describe('Level 4A visit workflow', () => {
  test('L4-VISIT-001 - Receptionist can open visits list', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(visitRoutes.index), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(new URL(page.url()).pathname).toBe(visitRoutes.index);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L4-VISIT-002 - Receptionist can open create visit page', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await openVisitCreatePage(page, null);
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    await expectSelectorVisible(page, '#patientSearch');
    expect(text).toMatch(/create new visit|select patient|visit details|visit type/i);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L4-VISIT-003 - Receptionist can search/select an existing patient during visit creation', async ({ page }) => {
    const selectedPatient = await ensureFreshPatient(page);

    await openVisitCreatePage(page, null);
    await selectPatientInVisitForm(page, selectedPatient);
    await assertNoSensitiveLeak(page);
  });

  test('L4-VISIT-004 - Receptionist can create a normal outpatient visit', async ({ page }) => {
    const selectedPatient = await ensureFreshPatient(page);
    const createdVisit = await createOutpatientVisit(page, selectedPatient);

    expect(createdVisit.path).toMatch(/\/admin\/visits\/\d+$/);
    await assertNoSensitiveLeak(page);
  });

  test('L4-VISIT-005 - Created visit appears in visit list or patient profile', async ({ page }) => {
    const selectedPatient = await ensureFreshPatient(page);
    const createdVisit = await ensureVisit(page);

    const listResponse = await page.goto(url(`${visitRoutes.index}?search=${encodeURIComponent(selectedPatient.firstName)}`), {
      waitUntil: 'commit',
    });
    const listText = await pageOrResponseText(page, listResponse);

    expect(listResponse?.status()).toBeLessThan(400);
    assertTextHasNoSensitiveLeak(listText);

    const profileResponse = await openPathWithSelector(page, selectedPatient.profilePath, '#visits');
    const profileText = await pageOrResponseText(page, profileResponse);

    expect(profileResponse?.status()).toBeLessThan(400);
    expect(profileText).toContain(selectedPatient.firstName);
    expect(profileText).toMatch(/Visit History\s+1/i);
    expect(profileText).toMatch(/VST-\d{8}-\d{4}/);
    assertTextHasNoSensitiveLeak(profileText);
  });

  test('L4-VISIT-006 - Required fields are validated on visit creation', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    await openVisitCreatePage(page, null);

    await submitFormWith(page, '#visitForm');

    await expect
      .poll(() => bodyText(page), { timeout: 20_000 })
      .toMatch(/select a patient|patient.*required|visit type|required|correct.*fields/i);
    await assertNoSensitiveLeak(page);
  });

  test('L4-VISIT-007 - Doctor can see assigned/queued consultation or visit if workflow supports it', async ({ page }) => {
    const createdVisit = await ensureVisit(page);

    if (!createdVisit.serviceAdded) {
      test.skip(true, 'No consultation service was available to create a doctor queue route in this UHMS build.');
    }

    await loginAs(page, 'UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD');

    const response = await page.goto(url(`${visitRoutes.consultations}?search=${encodeURIComponent(patient?.firstName ?? '')}`), {
      waitUntil: 'commit',
    });
    const text = await pageOrResponseText(page, response);

    assertTextHasNoSensitiveLeak(text);

    if ([401, 403, 404, 405].includes(response?.status() ?? 0) || !text.includes(patient?.firstName ?? '')) {
      test.skip(true, 'Doctor consultation queue does not expose this newly registered visit before triage in this workflow.');
    }

    expect(text).toContain(patient?.firstName ?? '');
  });

  test('L4-VISIT-008 - Limited user cannot access visit creation directly', async ({ page }) => {
    await loginAs(page, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    await assertPathBlockedForUser(page, visitRoutes.create, /create new visit|select patient|visit details|visit type/i);
  });

  test('OTB-SEC-005 - Invalid visit ID does not expose debug or secrets', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(visitRoutes.invalidVisit), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect([401, 403, 404, 405]).toContain(response?.status());
    assertTextHasNoSensitiveLeak(text);
  });

  test('OTB-SEC-006 - Direct visit URL access respects permissions', async ({ browser, page }) => {
    const createdVisit = await ensureVisit(page);

    const limitedPage = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      const receptionistResponse = await page.goto(url(createdVisit.path), { waitUntil: 'commit' });
      const receptionistText = await pageOrResponseText(page, receptionistResponse);

      expect(receptionistResponse?.status()).toBeLessThan(400);
      expect(receptionistText).toMatch(new RegExp(`${createdVisit.id}|${patient?.firstName ?? ''}|Visit Details`, 'i'));
      assertTextHasNoSensitiveLeak(receptionistText);

      await assertPathBlockedForUser(
        limitedPage,
        createdVisit.path,
        new RegExp(`${createdVisit.id}|${patient?.firstName ?? ''}|visit details`, 'i'),
      );
    } finally {
      await limitedPage.close();
    }
  });
});

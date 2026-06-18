import { execFileSync } from 'node:child_process';
import { expect, test, type Browser, type Page } from '@playwright/test';
import { createCashPatient, type TestPatient } from './helpers/patients';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';
import { laravelRoot } from './support/laravel-root';

const emergencyRoutes = {
  board: '/admin/emergency/board',
  create: '/admin/emergency/cases/create',
  invalidCase: '/admin/emergency/cases/999999999',
  consultations: '/admin/consultations',
  visitsCreate: '/admin/visits/create',
};

const emergencyTextPattern = /Emergency\s*\/\s*Casualty|Emergency Consultation|Emergency Session|Casualty|Emergency/i;
const emergencyCasePathPattern = /\/admin\/emergency\/cases\/(\d+)$/;

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

type CreatedEmergencyCase = {
  id: string;
  path: string;
};

type CreatedEmergencyVisit = {
  id: string;
  path: string;
  serviceAdded: boolean;
};

type EmergencyEvidence = {
  caseId: string | null;
  casePath: string | null;
  visitId: string | null;
  visitPath: string | null;
  hasCase: boolean;
  hasSession: boolean;
  hasEmergencyRoute: boolean;
  emergencyText: string;
  emergencyBillingItemNames: string[];
  invoicePath: string | null;
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';

let emergencyPatient: TestPatient | null = null;
let emergencyCase: CreatedEmergencyCase | null = null;
let emergencyVisit: CreatedEmergencyVisit | null = null;
let emergencyVisitWithNormalService: CreatedEmergencyVisit | null = null;

test.describe.configure({ timeout: 180_000 });

test.beforeAll(() => {
  seedEmergencyServiceFoundation();
  ensurePermissionE2EUsers();
});

test.afterAll(() => {
  cleanupPermissionE2EUsers();
});

function runPhpJson<T>(script: string, env: Record<string, string> = {}): T {
  return JSON.parse(
    execFileSync(phpBinary, ['-r', script], {
      cwd: laravelRoot,
      env: {
        ...process.env,
        ...env,
      },
      stdio: 'pipe',
    }).toString('utf8'),
  ) as T;
}

function seedEmergencyServiceFoundation() {
  runPhpJson<{ serviceId: string }>(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$department = App\Models\Department::updateOrCreate(
    ['code' => 'EMR'],
    [
        'name' => 'Emergency / Casualty',
        'type' => App\Enums\DepartmentType::CONSULTATION->value,
        'status' => 'active',
    ]
);

$service = App\Models\ServiceCatalog::updateOrCreate(
    ['code' => 'EMR-CON'],
    [
        'name' => 'Emergency Consultation',
        'category' => App\Enums\ServiceType::CONSULTATION->value,
        'price' => 250.00,
        'department_id' => $department->id,
        'department_type' => App\Enums\DepartmentType::CONSULTATION->value,
        'is_active' => true,
        'is_billable' => true,
        'overall_result_type' => App\Models\ServiceCatalog::OVERALL_RESULT_FREE_TEXT,
    ]
);

App\Models\Setting::setValue('emergency', 'default_consultation_service_id', $service->id, 'integer');

echo json_encode(['serviceId' => (string) $service->id], JSON_THROW_ON_ERROR);
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

async function ensureEmergencyPatient(page: Page) {
  if (!emergencyPatient) {
    emergencyPatient = await createCashPatient(page, 'E2EEmergency');
    return emergencyPatient;
  }

  await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
  return emergencyPatient;
}

function emergencyCasePathFromHref(href: string | null) {
  expect(href, 'Expected emergency case URL').toBeTruthy();

  const parsed = new URL(href ?? '', url('/'));
  const match = parsed.pathname.match(emergencyCasePathPattern);

  expect(match, `Expected emergency case URL, got ${href}`).not.toBeNull();

  return {
    id: match?.[1] ?? '',
    path: parsed.pathname,
  };
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

async function openEmergencyCreatePage(page: Page, selectedPatient?: TestPatient) {
  const path = selectedPatient ? `${emergencyRoutes.create}?patient_id=${selectedPatient.id}` : emergencyRoutes.create;

  if (!selectedPatient) {
    return page.goto(url(path), { waitUntil: 'commit' });
  }

  return openPathWithSelector(page, path, 'form[action*="/admin/emergency/cases"]');
}

async function submitEmergencyCaseForm(page: Page, selectedPatient: TestPatient) {
  expectEmergencyMappingConfigured();

  await openEmergencyCreatePage(page, selectedPatient);

  await page.locator('#emergencyPatientSearchValue').evaluate((input, patientId) => {
    if (!(input instanceof HTMLInputElement)) {
      throw new Error('Emergency patient hidden input was not available.');
    }

    input.value = String(patientId);
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }, selectedPatient.id);
  await page.locator('[name="arrival_mode"]').selectOption('WALK_IN');
  await page.locator('[name="arrival_time"]').fill(new Date().toISOString().slice(0, 16));
  await page.locator('[name="chief_complaint"]').fill(`E2E emergency case for ${selectedPatient.fullName}`);
  await page.locator('[name="initial_condition"]').fill('E2E stable emergency workflow check');

  const formPayload = await page.evaluate(() => {
    const form = document.querySelector('form[action*="/admin/emergency/cases"]');

    if (!(form instanceof HTMLFormElement)) {
      throw new Error('Emergency case form was not available.');
    }

    return {
      action: form.action,
      fields: Object.fromEntries(new FormData(form).entries()) as Record<string, string>,
    };
  });

  const response = await page.request.post(formPayload.action, {
    form: formPayload.fields,
    maxRedirects: 0,
    headers: {
      Accept: 'text/html,application/xhtml+xml',
      Referer: page.url(),
    },
  });
  const text = await response.text();
  const location = response.headers().location;
  const createdUrl = location ? new URL(location, url('/')).toString() : response.url();

  assertTextHasNoSensitiveLeak(text);

  const created = emergencyCasePathFromHref(createdUrl);
  expect(response.status(), `Emergency case creation failed with ${response.status()}: ${text.slice(0, 500)}`).toBeLessThan(400);

  emergencyCase = created;
  return created;
}

async function ensureEmergencyCase(page: Page) {
  if (emergencyCase) {
    return emergencyCase;
  }

  return submitEmergencyCaseForm(page, await ensureEmergencyPatient(page));
}

async function openVisitCreatePage(page: Page, selectedPatient: TestPatient) {
  return openPathWithSelector(page, `${emergencyRoutes.visitsCreate}?patient_id=${selectedPatient.id}`, '#visitForm');
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

async function createEmergencyVisit(page: Page, selectedPatient: TestPatient, withNormalService: boolean) {
  expectEmergencyMappingConfigured();

  await openVisitCreatePage(page, selectedPatient);

  await expect(page.locator('#patientId')).toHaveValue(selectedPatient.id);
  await page.locator('[name="visit_type"]').selectOption('emergency');
  await page.locator('[name="priority"]').selectOption('emergency');
  await page.locator('[name="chief_complaint"]').fill(
    withNormalService
      ? `E2E emergency visit with normal consultation service for ${selectedPatient.fullName}`
      : `E2E emergency visit for ${selectedPatient.fullName}`,
  );

  const serviceAdded = withNormalService ? await addFirstConsultationServiceIfAvailable(page) : false;
  const result = await submitVisitFormJson(page);

  expect(result.ok, `Emergency visit creation failed: ${JSON.stringify(result.payload)}`).toBe(true);
  expect(result.status).toBe(201);

  return {
    ...visitPathFromHref(result.payload.redirect_url ?? null),
    serviceAdded,
  };
}

function emergencyEvidenceByVisitOrCase(params: { visitId?: string; caseId?: string }) {
  return runPhpJson<EmergencyEvidence>(
    String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$caseId = getenv('UHMS_E2E_CASE_ID') ?: null;
$visitId = getenv('UHMS_E2E_VISIT_ID') ?: null;

$case = $caseId
    ? App\Models\EmergencyCase::query()->find($caseId)
    : null;

if (! $case && $visitId) {
    $case = App\Models\EmergencyCase::query()->where('visit_id', $visitId)->latest('id')->first();
}

$visit = $visitId
    ? App\Models\Visit::query()->find($visitId)
    : ($case?->visit);

if ($case) {
    $case->load([
        'activeEmergencySession.department',
        'emergencySessions.department',
        'visit.latestInvoice.items.serviceCatalog',
        'visit.consultationRoutes.department',
        'visit.consultationRoutes.service',
        'visit.consultationRoutes.routeServices.service',
    ]);
    $visit = $case->visit;
} elseif ($visit) {
    $visit->load([
        'latestInvoice.items.serviceCatalog',
        'emergencyCase.activeEmergencySession.department',
        'emergencyCase.emergencySessions.department',
        'consultationRoutes.department',
        'consultationRoutes.service',
        'consultationRoutes.routeServices.service',
    ]);
    $case = $visit->emergencyCase;
}

$routes = $visit?->consultationRoutes ?? collect();
$emergencyRoutes = $routes->filter(function ($route) {
    return $route->session_type === App\Models\VisitConsultationRoute::SESSION_TYPE_EMERGENCY || ! empty($route->emergency_case_id);
});
$session = $case?->activeEmergencySession ?: $case?->emergencySessions?->sortByDesc('id')->first();
$invoice = $visit?->latestInvoice;
$billingItems = $invoice?->items?->filter(fn ($item) => $item->source_type === 'emergency_service') ?? collect();
$billingNames = $billingItems->map(fn ($item) => $item->description ?: $item->serviceCatalog?->name)->filter()->values()->all();

$parts = collect([
    $visit?->visit_type?->value ?? $visit?->visit_type,
    $visit?->status?->value ?? $visit?->status,
    $visit?->priority?->value ?? $visit?->priority,
    $case?->emergency_number,
    $case?->emergency_status,
    $session?->status,
    $session?->department?->name,
]);

foreach ($emergencyRoutes as $route) {
    $parts->push($route->session_type);
    $parts->push($route->department?->name);
    $parts->push($route->service?->name);
    foreach ($route->routeServices ?? [] as $routeService) {
        $parts->push($routeService->service?->name);
    }
}

foreach ($billingNames as $name) {
    $parts->push($name);
}

echo json_encode([
    'caseId' => $case ? (string) $case->id : null,
    'casePath' => $case ? route('admin.emergency.cases.show', $case, false) : null,
    'visitId' => $visit ? (string) $visit->id : null,
    'visitPath' => $visit ? route('admin.visits.show', $visit, false) : null,
    'hasCase' => (bool) $case,
    'hasSession' => (bool) $session,
    'hasEmergencyRoute' => $emergencyRoutes->isNotEmpty(),
    'emergencyText' => $parts->filter()->implode(' '),
    'emergencyBillingItemNames' => $billingNames,
    'invoicePath' => $invoice ? route('admin.billing.invoices.show', $invoice, false) : null,
], JSON_THROW_ON_ERROR);
`,
    {
      UHMS_E2E_CASE_ID: params.caseId ?? '',
      UHMS_E2E_VISIT_ID: params.visitId ?? '',
    },
  );
}

function emergencyMappingStatus() {
  return runPhpJson<{ configured: boolean; serviceName: string | null; departmentName: string | null }>(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$configuredId = App\Models\Setting::getValue('emergency', 'default_consultation_service_id');
$service = null;

if ($configuredId) {
    $service = App\Models\ServiceCatalog::with('department')->whereKey($configuredId)->where('is_active', true)->first();
}

$department = null;

if (! $service) {
    $department = App\Models\Department::query()
        ->where(function ($query) {
            $query->whereRaw('LOWER(code) IN (?, ?, ?, ?, ?)', ['er', 'ed', 'emr', 'emer', 'emergency'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%emergency%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%casualty%'])
                ->orWhereRaw('LOWER(type) = ?', ['emergency']);
        })
        ->orderByRaw("CASE WHEN LOWER(code) IN ('er', 'ed', 'emr') THEN 0 ELSE 1 END")
        ->oldest('id')
        ->first();

    if ($department) {
        $service = App\Models\ServiceCatalog::with('department')
            ->where('department_id', $department->id)
            ->where('category', App\Enums\ServiceType::CONSULTATION->value)
            ->where('is_active', true)
            ->oldest('id')
            ->first();
    }
}

echo json_encode([
    'configured' => (bool) $service,
    'serviceName' => $service?->name,
    'departmentName' => $service?->department?->name ?? $department?->name,
], JSON_THROW_ON_ERROR);
`);
}

function expectEmergencyMappingConfigured() {
  const mapping = emergencyMappingStatus();

  expect(mapping.configured, 'Emergency / Casualty service mapping is required but was not found.').toBe(true);
  expect([mapping.serviceName, mapping.departmentName].filter(Boolean).join(' ')).toMatch(emergencyTextPattern);
}

function expectEmergencyEvidence(evidence: EmergencyEvidence) {
  expect(evidence.hasCase, `Expected emergency case evidence. Got: ${JSON.stringify(evidence)}`).toBe(true);
  expect(
    evidence.hasSession,
    'Emergency case was created but no Emergency / Casualty session was queued.',
  ).toBe(true);
  expect(
    evidence.hasEmergencyRoute,
    `Expected an Emergency / Casualty consultation route. Got: ${JSON.stringify(evidence)}`,
  ).toBe(true);
  expect(
    evidence.emergencyText,
    `Expected emergency-related session text such as Emergency / Casualty. Got: ${JSON.stringify(evidence)}`,
  ).toMatch(emergencyTextPattern);
}

function expectEmergencyBilling(evidence: EmergencyEvidence) {
  expect(
    evidence.emergencyBillingItemNames.length,
    'Emergency / Casualty service mapping is required but was not found.',
  ).toBeGreaterThan(0);
  expect(evidence.emergencyBillingItemNames.join(' ')).toMatch(emergencyTextPattern);
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

test.describe('Level 4B emergency workflow', () => {
  test('L4-EMG-001 - Receptionist can open emergency cases page', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(emergencyRoutes.board), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(new URL(page.url()).pathname).toBe(emergencyRoutes.board);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L4-EMG-002 - Receptionist can open create emergency case page', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await openEmergencyCreatePage(page);
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(new URL(page.url()).pathname).toBe(emergencyRoutes.create);
    expect(text).toMatch(/Emergency|Arrival|Patient/i);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L4-EMG-003 - Receptionist can create emergency case using existing patient', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const created = await submitEmergencyCaseForm(page, await ensureEmergencyPatient(page));
    const response = await page.goto(url(created.path), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(new URL(page.url()).pathname).toBe(created.path);
    expect(text).toMatch(emergencyTextPattern);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L4-EMG-004 - Activating emergency case queues Emergency / Casualty session', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const created = await ensureEmergencyCase(page);
    await page.goto(url(created.path), { waitUntil: 'commit' });

    const evidence = emergencyEvidenceByVisitOrCase({ caseId: created.id });

    expectEmergencyEvidence(evidence);
  });

  test('L4-EMG-005 - Creating visit with visit type Emergency automatically creates Emergency / Casualty session', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const selectedPatient = await createCashPatient(page, 'E2EEmergencyVisit');
    emergencyVisit = await createEmergencyVisit(page, selectedPatient, false);
    const evidence = emergencyEvidenceByVisitOrCase({ visitId: emergencyVisit.id });

    expectEmergencyEvidence(evidence);
  });

  test('L4-EMG-006 - Emergency visit is flagged/treated as emergency even if a normal consultation service is selected', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const selectedPatient = await createCashPatient(page, 'E2EEmergencySvc');
    emergencyVisitWithNormalService = await createEmergencyVisit(page, selectedPatient, true);
    const evidence = emergencyEvidenceByVisitOrCase({ visitId: emergencyVisitWithNormalService.id });

    expectEmergencyEvidence(evidence);
    expect(evidence.emergencyText).toMatch(emergencyTextPattern);
  });

  test('L4-EMG-007 - Emergency / Casualty mapped service is billed or visible for billing', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const created = await ensureEmergencyCase(page);
    const evidence = emergencyEvidenceByVisitOrCase({ caseId: created.id });

    expectEmergencyEvidence(evidence);
    expectEmergencyBilling(evidence);

    if (evidence.invoicePath) {
      const response = await page.goto(url(evidence.invoicePath), { waitUntil: 'commit' });
      const text = await pageOrResponseText(page, response);

      assertTextHasNoSensitiveLeak(text);
      expect(response?.status()).toBeLessThan(400);
      expect(text).toMatch(emergencyTextPattern);
    }
  });

  test('L4-EMG-008 - Limited user cannot access emergency case creation directly', async ({ browser }) => {
    const page = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      await assertPathBlockedForUser(page, emergencyRoutes.create, /Create Emergency|Arrival|Emergency Case/i);
    } finally {
      await page.close();
    }
  });

  test('OTB-SEC-007 - Invalid emergency case ID does not expose Laravel debug, SQL errors, stack traces, .env values, or file paths', async ({ page }) => {
    await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    const response = await page.goto(url(emergencyRoutes.invalidCase), { waitUntil: 'commit' });
    const status = response?.status() ?? 0;
    const text = await pageOrResponseText(page, response);

    assertTextHasNoSensitiveLeak(text);
    expect([401, 403, 404, 405]).toContain(status);
  });

  test('OTB-SEC-008 - Direct emergency case URL access respects permissions', async ({ browser }) => {
    const receptionistPage = await openNewLoggedInPage(browser, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');
    const limitedPage = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      const created = await ensureEmergencyCase(receptionistPage);
      const allowedResponse = await receptionistPage.goto(url(created.path), { waitUntil: 'commit' });
      const allowedText = await pageOrResponseText(receptionistPage, allowedResponse);

      expect(allowedResponse?.status()).toBeLessThan(400);
      expect(allowedText).toMatch(emergencyTextPattern);
      assertTextHasNoSensitiveLeak(allowedText);

      await assertPathBlockedForUser(limitedPage, created.path, emergencyTextPattern);
    } finally {
      await receptionistPage.close();
      await limitedPage.close();
    }
  });
});

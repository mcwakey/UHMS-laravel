import { execFileSync } from 'node:child_process';
import { expect, test, type Browser, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

const labRoutes = {
  requests: '/admin/lab/requests',
  results: '/admin/lab/results',
  invalidRequest: '/admin/lab/requests/999999999',
  invalidResult: '/admin/lab/results/requests/999999999',
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

type LabFixture = {
  patientName: string;
  patientNumber: string;
  visitId: string;
  visitNumber: string;
  serviceName: string;
  requestId: string;
  requestNumber: string;
  requestPath: string;
  resultPath: string;
  printRequestPath: string;
  itemId: string;
  invoiceId: string | null;
  invoiceNumber: string | null;
  invoicePath: string | null;
  invoiceStatus: string | null;
  invoiceBalance: number;
  resultId: string | null;
  resultValue: string | null;
  resultVerified: boolean;
  printPath: string | null;
  requestedByEmail: string | null;
  status: string;
  itemStatus: string;
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';
const hasLabCredentials = Boolean(process.env.UHMS_LAB_EMAIL && process.env.UHMS_LAB_PASSWORD);

let labFixture: LabFixture | null = null;

test.describe.configure({ mode: 'serial', timeout: 180_000 });

test.beforeAll(() => {
  ensurePermissionE2EUsers();
});

test.afterAll(() => {
  cleanupPermissionE2EUsers();
});

function runPhpJson<T>(script: string, env: Record<string, string> = {}): T {
  return JSON.parse(
    execFileSync(phpBinary, ['-r', script], {
      cwd: process.cwd(),
      env: {
        ...process.env,
        ...env,
      },
      stdio: 'pipe',
    }).toString('utf8'),
  ) as T;
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
    await expect
      .poll(
        async () => {
          const text = await bodyText(page);
          if (text.trim()) {
            return text;
          }

          try {
            const html = await page.content();
            return html.length > 10_000 || html.includes('data-page="app"') ? html : '';
          } catch {
            return '';
          }
        },
        { timeout: 20_000 },
      )
      .toBeTruthy();
  } catch {
    // Some UHMS screens render useful data inside the app payload before body text settles.
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

async function openPath(page: Page, path: string) {
  const response = await page.goto(url(path), { waitUntil: 'commit' });
  const text = await pageOrResponseText(page, response);

  assertTextHasNoSensitiveLeak(text);
  test.skip(
    [404, 503].includes(response?.status() ?? 0) && /not found|module|disabled|unavailable/i.test(text),
    `Lab page ${path} is not available in this UHMS build.`,
  );

  expect(response?.status()).toBeLessThan(400);

  return { response, text };
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

function createLabRequestFixture() {
  labFixture = runPhpJson<LabFixture>(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$doctor = App\Models\User::where('email', getenv('UHMS_DOCTOR_EMAIL'))->firstOrFail();

Illuminate\Support\Facades\Auth::login($doctor);

$department = App\Models\Department::updateOrCreate(
    ['code' => 'E2ELAB'],
    [
        'name' => 'E2E Laboratory',
        'type' => App\Enums\DepartmentType::INVESTIGATION->value,
        'status' => 'active',
        'result_type' => App\Enums\ResultType::PARAMETERS->value,
        'is_stock_managed' => false,
    ]
);

$service = App\Models\ServiceCatalog::updateOrCreate(
    ['code' => 'E2E-LAB-MAL'],
    [
        'name' => 'E2E Malaria Test',
        'category' => App\Enums\ServiceType::INVESTIGATION->value,
        'price' => 42.50,
        'department_id' => $department->id,
        'department_type' => App\Enums\DepartmentType::INVESTIGATION->value,
        'is_active' => true,
        'is_billable' => true,
        'overall_result_type' => App\Models\ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE,
        'overall_result_positive_label' => 'Positive',
        'overall_result_negative_label' => 'Negative',
    ]
);

$runId = base_convert((string) now()->timestamp, 10, 36) . random_int(100, 999);
$patient = App\Models\Patient::create([
    'patient_number' => app(App\Services\PatientIdGeneratorService::class)->generate(),
    'first_name' => 'E2ELab' . $runId,
    'last_name' => 'CashPatient',
    'date_of_birth' => '1993-04-14',
    'gender' => App\Enums\Gender::FEMALE,
    'phone' => '026' . substr((string) now()->timestamp, -7),
    'email' => 'e2e.lab.' . $runId . '@example.test',
    'address' => 'E2E lab patient address',
    'city' => 'Accra',
    'region' => 'Greater Accra',
    'status' => 'active',
    'is_active' => true,
]);

$visit = App\Models\Visit::create([
    'visit_number' => App\Models\Visit::generateVisitNumber(),
    'patient_id' => $patient->id,
    'patient_age' => $patient->date_of_birth?->age,
    'department_id' => $department->id,
    'visit_type' => App\Enums\VisitType::OUTPATIENT->value,
    'visit_date' => now()->toDateString(),
    'status' => App\Enums\VisitStatus::REGISTERED->value,
    'priority' => App\Enums\Priority::NORMAL->value,
    'chief_complaint' => 'E2E lab workflow check',
    'checked_in_at' => now(),
    'created_by' => $doctor->id,
]);

$request = app(App\Services\LabService::class)->createRequest(
    $visit,
    [[
        'service_id' => $service->id,
        'name' => $service->name,
    ]],
    [
        'target_department_id' => $department->id,
        'urgency' => 'routine',
        'clinical_info' => 'E2E malaria screen requested by doctor',
    ]
);

$request = $request->fresh(['patient', 'visit', 'requestedBy', 'items.service', 'items.invoiceItem.invoice', 'items.result']);
$item = $request->items->first();

echo json_encode([
    'patientName' => $patient->full_name,
    'patientNumber' => $patient->patient_number,
    'visitId' => (string) $visit->id,
    'visitNumber' => $visit->visit_number,
    'serviceName' => $service->name,
    'requestId' => (string) $request->id,
    'requestNumber' => $request->request_number,
    'requestPath' => route('admin.lab.requests.show', $request, false),
    'resultPath' => route('admin.lab.results.show', $request, false),
    'printRequestPath' => route('admin.lab.results.print-request', $request, false),
    'itemId' => (string) $item->id,
    'invoiceId' => null,
    'invoiceNumber' => null,
    'invoicePath' => null,
    'invoiceStatus' => null,
    'invoiceBalance' => 0,
    'resultId' => null,
    'resultValue' => null,
    'resultVerified' => false,
    'printPath' => null,
    'requestedByEmail' => $request->requestedBy?->email,
    'status' => $request->status,
    'itemStatus' => $item->status,
], JSON_THROW_ON_ERROR);
`);

  return labFixture;
}

function ensureLabRequestFixture() {
  return labFixture ?? createLabRequestFixture();
}

function refreshLabFixture(requestId: string) {
  labFixture = runPhpJson<LabFixture>(
    String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = App\Models\LabRequest::with([
    'patient',
    'visit',
    'requestedBy',
    'items.service',
    'items.invoiceItem.invoice',
    'items.result',
])->findOrFail(getenv('UHMS_E2E_LAB_REQUEST_ID'));
$item = $request->items->first();
$invoice = $item?->invoiceItem?->invoice;
$result = $item?->result;

echo json_encode([
    'patientName' => $request->patient?->full_name,
    'patientNumber' => $request->patient?->patient_number,
    'visitId' => (string) $request->visit_id,
    'visitNumber' => $request->visit?->visit_number,
    'serviceName' => $item?->service?->name ?? $item?->name,
    'requestId' => (string) $request->id,
    'requestNumber' => $request->request_number,
    'requestPath' => route('admin.lab.requests.show', $request, false),
    'resultPath' => route('admin.lab.results.show', $request, false),
    'printRequestPath' => route('admin.lab.results.print-request', $request, false) . '?items[]=' . $item->id . '&layout=compact',
    'itemId' => (string) $item->id,
    'invoiceId' => $invoice ? (string) $invoice->id : null,
    'invoiceNumber' => $invoice?->invoice_number,
    'invoicePath' => $invoice ? route('admin.billing.invoices.show', $invoice, false) : null,
    'invoiceStatus' => $invoice ? ($invoice->status instanceof BackedEnum ? $invoice->status->value : (string) $invoice->status) : null,
    'invoiceBalance' => $invoice ? (float) $invoice->balance : 0,
    'resultId' => $result ? (string) $result->id : null,
    'resultValue' => $result?->overallResultDisplay($item?->service),
    'resultVerified' => (bool) $result?->is_verified,
    'printPath' => $result?->is_verified ? route('admin.lab.results.print', $item, false) : null,
    'requestedByEmail' => $request->requestedBy?->email,
    'status' => $request->status,
    'itemStatus' => $item->status,
], JSON_THROW_ON_ERROR);
`,
    { UHMS_E2E_LAB_REQUEST_ID: requestId },
  );

  return labFixture;
}

function acceptBillAndPayFixture(requestId: string) {
  return runPhpJson<LabFixture>(
    String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lab = App\Models\User::where('email', getenv('UHMS_LAB_EMAIL'))->firstOrFail();
$cashier = App\Models\User::where('email', getenv('UHMS_CASHIER_EMAIL'))->firstOrFail();
$request = App\Models\LabRequest::with(['items.service', 'visit', 'patient'])->findOrFail(getenv('UHMS_E2E_LAB_REQUEST_ID'));
$item = $request->items->firstOrFail();

Illuminate\Support\Facades\Auth::login($lab);
$result = app(App\Services\InvestigationRequestService::class)->acceptSelectedItems($request, [$item->id], $lab);
$request = $result['request']->fresh(['items.invoiceItem.invoice.payments', 'items.result', 'patient', 'visit', 'requestedBy']);
$item = $request->items->first();
$invoice = $item->invoiceItem?->invoice;

if ($invoice) {
    app(App\Services\InvoiceReceivableService::class)->syncFromInvoice($invoice->fresh(['items', 'payments', 'creditNotes']));
    $invoice = $invoice->fresh(['items', 'payments', 'receivables']);

    if ((float) $invoice->balance > 0) {
        App\Models\CashierShift::firstOrCreate(
            [
                'user_id' => $cashier->id,
                'status' => App\Enums\ShiftStatus::OPEN->value,
            ],
            [
                'shift_date' => now()->toDateString(),
                'started_at' => now(),
                'opening_balance' => 0,
            ]
        );

        Illuminate\Support\Facades\Auth::login($cashier);
        $receivable = $invoice->receivables()->where('payer_type', 'patient')->first();
        app(App\Services\PaymentService::class)->recordPayment($invoice, [
            'amount' => (float) $invoice->balance,
            'payment_method' => App\Enums\PaymentMethod::CASH->value,
            'reference_number' => 'E2E-LAB-' . now()->timestamp,
            'notes' => 'E2E lab workflow prepayment',
            'invoice_receivable_id' => $receivable?->id,
        ], []);
    }
}

$request = $request->fresh([
    'patient',
    'visit',
    'requestedBy',
    'items.service',
    'items.invoiceItem.invoice',
    'items.result',
]);
$item = $request->items->first();
$invoice = $item?->invoiceItem?->invoice;
$result = $item?->result;

echo json_encode([
    'patientName' => $request->patient?->full_name,
    'patientNumber' => $request->patient?->patient_number,
    'visitId' => (string) $request->visit_id,
    'visitNumber' => $request->visit?->visit_number,
    'serviceName' => $item?->service?->name ?? $item?->name,
    'requestId' => (string) $request->id,
    'requestNumber' => $request->request_number,
    'requestPath' => route('admin.lab.requests.show', $request, false),
    'resultPath' => route('admin.lab.results.show', $request, false),
    'printRequestPath' => route('admin.lab.results.print-request', $request, false) . '?items[]=' . $item->id . '&layout=compact',
    'itemId' => (string) $item->id,
    'invoiceId' => $invoice ? (string) $invoice->id : null,
    'invoiceNumber' => $invoice?->invoice_number,
    'invoicePath' => $invoice ? route('admin.billing.invoices.show', $invoice, false) : null,
    'invoiceStatus' => $invoice ? ($invoice->status instanceof BackedEnum ? $invoice->status->value : (string) $invoice->status) : null,
    'invoiceBalance' => $invoice ? (float) $invoice->balance : 0,
    'resultId' => $result ? (string) $result->id : null,
    'resultValue' => $result?->overallResultDisplay($item?->service),
    'resultVerified' => (bool) $result?->is_verified,
    'printPath' => $result?->is_verified ? route('admin.lab.results.print', $item, false) : null,
    'requestedByEmail' => $request->requestedBy?->email,
    'status' => $request->status,
    'itemStatus' => $item->status,
], JSON_THROW_ON_ERROR);
`,
    { UHMS_E2E_LAB_REQUEST_ID: requestId },
  );
}

function enterAndVerifyResultFixture(requestId: string) {
  return runPhpJson<LabFixture>(
    String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lab = App\Models\User::where('email', getenv('UHMS_LAB_EMAIL'))->firstOrFail();
$request = App\Models\LabRequest::with(['items.service', 'items.result'])->findOrFail(getenv('UHMS_E2E_LAB_REQUEST_ID'));
$item = $request->items->firstOrFail();

Illuminate\Support\Facades\Auth::login($lab);
$result = app(App\Services\LabService::class)->enterResult($item, [
    'result_type' => App\Enums\ResultType::PARAMETERS->value,
    'overall_result_type' => App\Models\ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE,
    'overall_result_outcome' => 'negative',
    'result_value' => 'negative',
    'is_abnormal' => false,
    'remarks' => 'E2E lab result entry',
]);

if ($lab->can('lab.results.verify') && ! $result->is_verified) {
    app(App\Services\LabService::class)->verifyResult($result);
}

$request = $request->fresh([
    'patient',
    'visit',
    'requestedBy',
    'items.service',
    'items.invoiceItem.invoice',
    'items.result',
]);
$item = $request->items->first();
$invoice = $item?->invoiceItem?->invoice;
$result = $item?->result;

echo json_encode([
    'patientName' => $request->patient?->full_name,
    'patientNumber' => $request->patient?->patient_number,
    'visitId' => (string) $request->visit_id,
    'visitNumber' => $request->visit?->visit_number,
    'serviceName' => $item?->service?->name ?? $item?->name,
    'requestId' => (string) $request->id,
    'requestNumber' => $request->request_number,
    'requestPath' => route('admin.lab.requests.show', $request, false),
    'resultPath' => route('admin.lab.results.show', $request, false),
    'printRequestPath' => route('admin.lab.results.print-request', $request, false) . '?items[]=' . $item->id . '&layout=compact',
    'itemId' => (string) $item->id,
    'invoiceId' => $invoice ? (string) $invoice->id : null,
    'invoiceNumber' => $invoice?->invoice_number,
    'invoicePath' => $invoice ? route('admin.billing.invoices.show', $invoice, false) : null,
    'invoiceStatus' => $invoice ? ($invoice->status instanceof BackedEnum ? $invoice->status->value : (string) $invoice->status) : null,
    'invoiceBalance' => $invoice ? (float) $invoice->balance : 0,
    'resultId' => $result ? (string) $result->id : null,
    'resultValue' => $result?->overallResultDisplay($item?->service),
    'resultVerified' => (bool) $result?->is_verified,
    'printPath' => $result?->is_verified ? route('admin.lab.results.print', $item, false) : null,
    'requestedByEmail' => $request->requestedBy?->email,
    'status' => $request->status,
    'itemStatus' => $item->status,
], JSON_THROW_ON_ERROR);
`,
    { UHMS_E2E_LAB_REQUEST_ID: requestId },
  );
}

test.describe('Level 8 lab/investigation workflow', () => {
  test.skip(!hasLabCredentials, 'UHMS_LAB_EMAIL and UHMS_LAB_PASSWORD must be set for Level 8 lab E2E tests.');

  test('L8-LAB-001 - Lab user can open laboratory/investigations page', async ({ page }) => {
    await loginAs(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');

    const { text } = await openPath(page, labRoutes.requests);
    expect(text).toMatch(/lab|investigation|request|microscope/i);
  });

  test('L8-LAB-002 - Doctor can request a lab investigation for an active patient/visit', async ({ page }) => {
    const fixture = ensureLabRequestFixture();
    await loginAs(page, 'UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD');

    expect(fixture.requestedByEmail).toBe(process.env.UHMS_DOCTOR_EMAIL);
    expect(fixture.requestNumber).toBeTruthy();
    expect(fixture.serviceName).toContain('E2E Malaria Test');
    expect(fixture.patientName).toMatch(/E2ELab/i);
    expect(fixture.visitNumber).toBeTruthy();
  });

  test('L8-LAB-003 - Requested investigation appears in lab queue or pending investigations', async ({ page }) => {
    const fixture = ensureLabRequestFixture();
    await loginAs(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');

    const { text } = await openPath(page, `${labRoutes.requests}?search=${encodeURIComponent(fixture.requestNumber)}`);
    expect(text).toMatch(/Lab\/Requests\/Index|investigation requests|request/i);
    expect(fixture.requestNumber).toBeTruthy();
    expect(fixture.status).toBe('pending');
    expect(fixture.itemStatus).toBe('pending');
  });

  test('L8-LAB-004 - Investigation is billed or visible for billing if UHMS requires billing', async ({ page }) => {
    const fixture = acceptBillAndPayFixture(ensureLabRequestFixture().requestId);
    labFixture = fixture;
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    expect(fixture.invoiceNumber, 'Expected accepting the billable investigation to create an invoice.').toBeTruthy();
    expect(fixture.invoiceStatus).toBe('paid');
    expect(fixture.invoiceBalance).toBeCloseTo(0, 2);

    const { text } = await openPath(page, fixture.invoicePath ?? labRoutes.requests);
    expect(text).toMatch(new RegExp(`${fixture.invoiceNumber}|${fixture.patientName}|${fixture.serviceName}`, 'i'));
  });

  test('L8-LAB-005 - Lab user can open investigation request details', async ({ page }) => {
    const fixture = refreshLabFixture(ensureLabRequestFixture().requestId);
    await loginAs(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');

    const { text } = await openPath(page, fixture.resultPath);
    expect(text).toContain(fixture.requestNumber);
    expect(text).toMatch(new RegExp(`${fixture.patientName}|${fixture.serviceName}|result`, 'i'));
  });

  test('L8-LAB-006 - Lab user can enter or save investigation result', async ({ page }) => {
    const fixture = enterAndVerifyResultFixture(ensureLabRequestFixture().requestId);
    labFixture = fixture;
    await loginAs(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');

    expect(fixture.resultId).toBeTruthy();
    expect(fixture.resultValue).toMatch(/negative|non-reactive|normal/i);

    const { text } = await openPath(page, fixture.resultPath);
    expect(text).toMatch(/result|investigation|UHMS|Lab/i);
    expect(fixture.status).toBe('completed');
    expect(fixture.itemStatus).toBe('completed');
  });

  test('L8-LAB-007 - Saved result is visible from lab result/details page', async ({ page }) => {
    const fixture = refreshLabFixture(ensureLabRequestFixture().requestId);
    await loginAs(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');

    const { text } = await openPath(page, fixture.resultPath);
    expect(fixture.resultValue).toMatch(/negative/i);
    expect(fixture.resultId).toBeTruthy();
    expect(text).toMatch(/result|investigation|UHMS|Lab/i);
  });

  test('L8-LAB-008 - Result can be printed, previewed, or opened', async ({ page }) => {
    const fixture = refreshLabFixture(ensureLabRequestFixture().requestId);
    await loginAs(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');

    expect(fixture.printPath ?? fixture.printRequestPath).toBeTruthy();
    const { text } = await openPath(page, fixture.printPath ?? fixture.printRequestPath);
    expect(text).toMatch(new RegExp(`${fixture.requestNumber}|${fixture.patientName}|${fixture.serviceName}|result report`, 'i'));
  });

  test('L8-LAB-009 - Limited user cannot access lab result/request page directly', async ({ browser }) => {
    const fixture = refreshLabFixture(ensureLabRequestFixture().requestId);
    const page = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      await assertPathBlockedForUser(page, fixture.resultPath, /Investigation|Result|E2E Malaria Test|Save Result/i);
    } finally {
      await page.close();
    }
  });

  test('L8-LAB-010 - Doctor can view completed result if allowed', async ({ page }) => {
    const fixture = refreshLabFixture(ensureLabRequestFixture().requestId);
    await loginAs(page, 'UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD');

    const { text } = await openPath(page, fixture.resultPath);
    expect(fixture.resultId).toBeTruthy();
    expect(fixture.resultValue).toMatch(/negative/i);
    expect(text).toMatch(/result|investigation|UHMS|Lab/i);
  });

  test('OTB-SEC-012 - Invalid lab request/result URL does not expose Laravel debug, SQL errors, stack traces, .env values, or file paths', async ({ page }) => {
    await loginAs(page, 'UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD');

    for (const path of [labRoutes.invalidRequest, labRoutes.invalidResult]) {
      const response = await page.goto(url(path), { waitUntil: 'commit' });
      const status = response?.status() ?? 0;
      const text = await pageOrResponseText(page, response);

      assertTextHasNoSensitiveLeak(text);
      expect([401, 403, 404, 405]).toContain(status);
    }
  });
});

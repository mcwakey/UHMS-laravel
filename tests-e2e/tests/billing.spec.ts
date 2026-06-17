import { execFileSync } from 'node:child_process';
import { expect, test, type Browser, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

const billingRoutes = {
  invoices: '/admin/billing/invoices',
  paymentsReceive: '/admin/billing/payments/receive',
  invalidInvoice: '/admin/billing/invoices/999999999',
  invalidReceipt: '/admin/billing/payments/999999999/receipt',
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

type BillingFixture = {
  patientId: string;
  patientName: string;
  patientNumber: string;
  serviceName: string;
  serviceCode: string;
  invoiceId: string;
  invoiceNumber: string;
  invoicePath: string;
  paymentStorePath: string;
  totalAmount: number;
  balance: number;
  lineTotals: number[];
};

type InvoiceEvidence = BillingFixture & {
  amountPaid: number;
  status: string;
  paymentCount: number;
  latestPaymentId: string | null;
  latestPaymentNumber: string | null;
  latestReceiptPath: string | null;
};

type PaymentResponse = {
  ok: boolean;
  status: number;
  payload: Record<string, unknown>;
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';

let billingFixture: BillingFixture | null = null;
let paymentReceiptPath: string | null = null;

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
  await page.waitForLoadState('domcontentloaded', { timeout: 15_000 }).catch(() => undefined);

  try {
    await expect.poll(() => bodyText(page), { timeout: 12_000 }).toBeTruthy();
  } catch {
    // Some UHMS screens can briefly expose the app shell before body text settles.
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

function money(amount: number) {
  return amount.toFixed(2);
}

function createBillingFixture() {
  billingFixture = runPhpJson<BillingFixture>(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$receptionEmail = getenv('UHMS_RECEPTION_EMAIL');
$cashierEmail = getenv('UHMS_CASHIER_EMAIL');
$reception = App\Models\User::where('email', $receptionEmail)->firstOrFail();
$cashier = App\Models\User::where('email', $cashierEmail)->firstOrFail();

Illuminate\Support\Facades\Auth::login($reception);

$department = App\Models\Department::updateOrCreate(
    ['code' => 'E2EBIL'],
    [
        'name' => 'E2E Billing',
        'type' => App\Enums\DepartmentType::CONSULTATION->value,
        'status' => 'active',
    ]
);

$service = App\Models\ServiceCatalog::updateOrCreate(
    ['code' => 'E2E-BILL-CON'],
    [
        'name' => 'E2E Billing Consultation',
        'category' => App\Enums\ServiceType::CONSULTATION->value,
        'price' => 123.45,
        'department_id' => $department->id,
        'department_type' => App\Enums\DepartmentType::CONSULTATION->value,
        'is_active' => true,
        'is_billable' => true,
        'overall_result_type' => App\Models\ServiceCatalog::OVERALL_RESULT_FREE_TEXT,
    ]
);

$runId = base_convert((string) now()->timestamp, 10, 36) . random_int(100, 999);
$patient = App\Models\Patient::create([
    'patient_number' => app(App\Services\PatientIdGeneratorService::class)->generate(),
    'first_name' => 'E2EBilling' . $runId,
    'last_name' => 'CashPatient',
    'date_of_birth' => '1991-02-10',
    'gender' => App\Enums\Gender::MALE,
    'phone' => '024' . substr((string) now()->timestamp, -7),
    'email' => 'e2e.billing.' . $runId . '@example.test',
    'address' => 'E2E billing patient address',
    'city' => 'Accra',
    'region' => 'Greater Accra',
    'status' => 'active',
    'is_active' => true,
]);

$visit = App\Models\Visit::create([
    'visit_number' => App\Models\Visit::generateVisitNumber(),
    'patient_id' => $patient->id,
    'patient_age' => $patient->date_of_birth?->age,
    'visit_type' => App\Enums\VisitType::OUTPATIENT->value,
    'visit_date' => now()->toDateString(),
    'status' => App\Enums\VisitStatus::REGISTERED->value,
    'priority' => App\Enums\Priority::NORMAL->value,
    'chief_complaint' => 'E2E billing and payment check',
    'checked_in_at' => now(),
    'created_by' => $reception->id,
]);

$item = app(App\Services\BillingService::class)->addItemToVisitInvoice(
    visit: $visit,
    service: $service,
    sourceType: App\Models\InvoiceItem::SOURCE_CONSULTATION_SERVICE,
    sourceId: $visit->id,
    quantity: 1,
    departmentId: $department->id,
    description: $service->name,
);

$invoice = $item->invoice->fresh(['items', 'receivables']);
app(App\Services\InvoiceReceivableService::class)->syncFromInvoice($invoice->fresh(['items', 'payments', 'creditNotes']));
$invoice = $invoice->fresh(['items', 'receivables']);

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

echo json_encode([
    'patientId' => (string) $patient->id,
    'patientName' => $patient->full_name,
    'patientNumber' => $patient->patient_number,
    'serviceName' => $service->name,
    'serviceCode' => $service->code,
    'invoiceId' => (string) $invoice->id,
    'invoiceNumber' => $invoice->invoice_number,
    'invoicePath' => route('admin.billing.invoices.show', $invoice, false),
    'paymentStorePath' => route('admin.billing.payments.store', $invoice, false),
    'totalAmount' => (float) $invoice->total_amount,
    'balance' => (float) $invoice->balance,
    'lineTotals' => $invoice->items->map(fn ($item) => (float) $item->patient_payable)->values()->all(),
], JSON_THROW_ON_ERROR);
`);

  return billingFixture;
}

function ensureBillingFixture() {
  return billingFixture ?? createBillingFixture();
}

function invoiceEvidence(invoiceId: string) {
  return runPhpJson<InvoiceEvidence>(
    String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$invoice = App\Models\Invoice::with(['items.serviceCatalog', 'patient', 'payments' => fn ($query) => $query->latest('id')])
    ->findOrFail(getenv('UHMS_E2E_INVOICE_ID'));
$payment = $invoice->payments->first();

echo json_encode([
    'patientId' => (string) $invoice->patient_id,
    'patientName' => $invoice->patient?->full_name,
    'patientNumber' => $invoice->patient?->patient_number,
    'serviceName' => $invoice->items->first()?->description,
    'serviceCode' => $invoice->items->first()?->serviceCatalog?->code,
    'invoiceId' => (string) $invoice->id,
    'invoiceNumber' => $invoice->invoice_number,
    'invoicePath' => route('admin.billing.invoices.show', $invoice, false),
    'paymentStorePath' => route('admin.billing.payments.store', $invoice, false),
    'totalAmount' => (float) $invoice->total_amount,
    'balance' => (float) $invoice->balance,
    'lineTotals' => $invoice->items->map(fn ($item) => (float) $item->patient_payable)->values()->all(),
    'amountPaid' => (float) $invoice->amount_paid,
    'status' => $invoice->status instanceof BackedEnum ? $invoice->status->value : (string) $invoice->status,
    'paymentCount' => $invoice->payments->count(),
    'latestPaymentId' => $payment ? (string) $payment->id : null,
    'latestPaymentNumber' => $payment?->payment_number,
    'latestReceiptPath' => $payment ? route('admin.billing.payments.receipt', $payment, false) : null,
], JSON_THROW_ON_ERROR);
`,
    { UHMS_E2E_INVOICE_ID: invoiceId },
  );
}

async function submitPayment(page: Page, fixture: BillingFixture, amount: number) {
  return page.evaluate(
    async ({ action, amountValue }) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
      const body = new FormData();
      body.set('_token', token);
      body.set('amount', amountValue);
      body.set('payment_method', 'cash');
      body.set('reference_number', `E2E-${Date.now()}`);
      body.set('notes', 'E2E billing suite payment check');

      const receivable = document.querySelector('#invoiceReceivableSelect');
      if (receivable instanceof HTMLSelectElement && receivable.value) {
        body.set('invoice_receivable_id', receivable.value);
      }

      const response = await fetch(action, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body,
      });

      const contentType = response.headers.get('content-type') || '';
      const payload = contentType.includes('application/json') ? await response.json() : { text: await response.text() };

      return {
        ok: response.ok,
        status: response.status,
        payload,
      };
    },
    { action: fixture.paymentStorePath, amountValue: money(amount) },
  ) as Promise<PaymentResponse>;
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

test.describe('Level 6 billing and payments', () => {
  test('L6-BILL-001 - Cashier can open billing/invoices page', async ({ page }) => {
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    const response = await page.goto(url(billingRoutes.invoices), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(new URL(page.url()).pathname).toBe(billingRoutes.invoices);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L6-BILL-002 - Cashier can find or open an unpaid invoice created by an automated test workflow', async ({ page }) => {
    const fixture = ensureBillingFixture();
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    const response = await page.goto(url(`${billingRoutes.invoices}?search=${encodeURIComponent(fixture.invoiceNumber)}`), {
      waitUntil: 'commit',
    });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(text).toContain(fixture.invoiceNumber);
    expect(text).toContain(fixture.patientName);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L6-BILL-003 - Invoice shows correct patient and billed service', async ({ page }) => {
    const fixture = ensureBillingFixture();
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    const response = await openPathWithSelector(page, fixture.invoicePath, '#paymentForm');
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(text).toContain(fixture.patientName);
    expect(text).toContain(fixture.patientNumber);
    expect(text).toContain(fixture.serviceName);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L6-BILL-004 - Invoice total equals visible line totals', async ({ page }) => {
    const fixture = ensureBillingFixture();
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    const response = await openPathWithSelector(page, fixture.invoicePath, '#invoiceBalanceValue');
    const text = await pageOrResponseText(page, response);
    const evidence = invoiceEvidence(fixture.invoiceId);
    const lineSum = evidence.lineTotals.reduce((sum, amount) => sum + amount, 0);

    expect(response?.status()).toBeLessThan(400);
    expect(lineSum).toBeCloseTo(evidence.totalAmount, 2);
    expect(text).toContain(money(evidence.totalAmount));
    for (const lineTotal of evidence.lineTotals) {
      expect(text).toContain(money(lineTotal));
    }
    assertTextHasNoSensitiveLeak(text);
  });

  test('L6-BILL-008 - Negative payment is rejected', async ({ page }) => {
    const fixture = ensureBillingFixture();
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    await openPathWithSelector(page, fixture.invoicePath, '#paymentForm');

    const result = await submitPayment(page, fixture, -1);

    expect(result.ok).toBe(false);
    expect([422, 400]).toContain(result.status);
    expect(JSON.stringify(result.payload)).toMatch(/amount|min|0\.01|invalid|required/i);
  });

  test('L6-BILL-009 - Payment greater than invoice balance is rejected or handled safely according to UHMS rules', async ({ page }) => {
    const fixture = ensureBillingFixture();
    const before = invoiceEvidence(fixture.invoiceId);
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    await openPathWithSelector(page, fixture.invoicePath, '#paymentForm');

    const result = await submitPayment(page, fixture, before.balance + 100);
    const after = invoiceEvidence(fixture.invoiceId);

    expect(result.ok).toBe(false);
    expect([409, 422, 400]).toContain(result.status);
    expect(JSON.stringify(result.payload)).toMatch(/exceed|balance|cannot|invalid|amount/i);
    expect(after.amountPaid).toBeCloseTo(before.amountPaid, 2);
  });

  test('L6-BILL-005 - Cashier can record full cash payment', async ({ page }) => {
    const fixture = ensureBillingFixture();
    const before = invoiceEvidence(fixture.invoiceId);
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    await openPathWithSelector(page, fixture.invoicePath, '#paymentForm');

    const result = await submitPayment(page, fixture, before.balance);

    expect(result.ok, `Payment failed: ${JSON.stringify(result.payload)}`).toBe(true);
    expect(result.status).toBe(201);
    expect(result.payload.receipt_url).toBeTruthy();
    paymentReceiptPath = new URL(String(result.payload.receipt_url), url('/')).pathname;
  });

  test('L6-BILL-006 - Paid invoice status changes to paid or fully paid', async ({ page }) => {
    const fixture = ensureBillingFixture();
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    const evidence = invoiceEvidence(fixture.invoiceId);
    const response = await page.goto(url(fixture.invoicePath), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(evidence.status).toBe('paid');
    expect(evidence.balance).toBeCloseTo(0, 2);
    expect(evidence.amountPaid).toBeCloseTo(evidence.totalAmount, 2);
    expect(text).toMatch(/paid|fully paid/i);
    assertTextHasNoSensitiveLeak(text);
  });

  test('L6-BILL-007 - Receipt can be opened or printed after payment', async ({ page }) => {
    const fixture = ensureBillingFixture();
    const evidence = invoiceEvidence(fixture.invoiceId);
    const receiptPath = paymentReceiptPath ?? evidence.latestReceiptPath;

    expect(receiptPath, 'Expected a receipt URL after payment').toBeTruthy();

    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    const response = await page.goto(url(receiptPath ?? ''), { waitUntil: 'commit' });
    const text = await pageOrResponseText(page, response);

    expect(response?.status()).toBeLessThan(400);
    expect(text).toContain(evidence.latestPaymentNumber ?? '');
    expect(text).toContain(fixture.patientName);
    expect(text).toMatch(/receipt|print/i);
    assertTextHasNoSensitiveLeak(text);
  });

  test('OTB-SEC-009 - Limited user cannot access payment page directly', async ({ browser }) => {
    const page = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      await assertPathBlockedForUser(page, billingRoutes.paymentsReceive, /Outstanding Invoices|Receive Payment|Payment History/i);
    } finally {
      await page.close();
    }
  });

  test('OTB-SEC-010 - Invalid invoice/payment URL does not expose Laravel debug, SQL errors, stack traces, .env values, or file paths', async ({ page }) => {
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');

    for (const path of [billingRoutes.invalidInvoice, billingRoutes.invalidReceipt]) {
      const response = await page.goto(url(path), { waitUntil: 'commit' });
      const status = response?.status() ?? 0;
      const text = await pageOrResponseText(page, response);

      assertTextHasNoSensitiveLeak(text);
      expect([401, 403, 404, 405]).toContain(status);
    }
  });
});

import { execFileSync } from 'node:child_process';
import { expect, test, type Browser, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';
import { laravelRoot } from './support/laravel-root';

const reportRoutes = {
  dashboard: '/admin/reports/dashboard',
  income: '/admin/reports/income',
  incomeExport: '/admin/reports/income?export=pdf',
  dailyCollection: '/admin/reports/daily-collection',
  payments: '/admin/billing/payments',
  accountingReport: '/admin/accounting/trial-balance',
  accountingTrialBalanceExport: '/admin/accounting/trial-balance/export',
  invalidReport: '/admin/reports/not-a-real-report',
  invalidExport: '/admin/reports/income?export=definitely-invalid',
  invalidAccountingReport: '/admin/accounting/reports/not-a-real-report',
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

type ReportFixture = {
  patientName: string;
  patientNumber: string;
  serviceName: string;
  invoiceId: string;
  invoiceNumber: string;
  invoicePath: string;
  paymentId: string;
  paymentNumber: string;
  paymentReference: string;
  paymentPath: string;
  receiptPath: string;
  receiptPdfPath: string;
  paymentDate: string;
  totalAmount: number;
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';
const hasAccountantCredentials = Boolean(process.env.UHMS_ACCOUNTANT_EMAIL && process.env.UHMS_ACCOUNTANT_PASSWORD);

let reportFixture: ReportFixture | null = null;

test.describe.configure({ mode: 'serial', timeout: 180_000 });

test.beforeAll(() => {
  ensurePermissionE2EUsers();
  enableReportModules();
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

function runPhp(script: string) {
  execFileSync(phpBinary, ['-r', script], {
    cwd: laravelRoot,
    env: {
      ...process.env,
    },
    stdio: 'pipe',
  });
}

function enableReportModules() {
  runPhp(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (['reports', 'billing', 'accounting_basic', 'accounting_advanced'] as $slug) {
    $module = App\Models\Module::where('slug', $slug)->first();
    if ($module) {
        $module->forceFill(['is_enabled' => true])->save();
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
    // Denied/download-ish responses can still be checked through html/response text.
  }

  const renderedText = await bodyText(page);
  if (renderedText.trim()) {
    return renderedText;
  }

  try {
    const html = await page.content();
    if (html.trim()) {
      if (/uhms-loading/i.test(html) && !/<body[\s>]/i.test(html)) {
        await page.reload({ waitUntil: 'commit' }).catch(() => undefined);
        await page.waitForSelector('body', { state: 'attached', timeout: 15_000 }).catch(() => undefined);
        const retryText = await bodyText(page);
        if (retryText.trim()) {
          return retryText;
        }
      }

      return html;
    }
  } catch {
    // Fall back to response body.
  }

  try {
    return response ? await response.text() : '';
  } catch {
    return '';
  }
}

function today() {
  return new Date().toISOString().slice(0, 10);
}

function withDateRange(path: string) {
  const separator = path.includes('?') ? '&' : '?';
  const day = today();

  return `${path}${separator}date_from=${day}&date_to=${day}`;
}

async function openPath(page: Page, path: string, expectedText: RegExp) {
  let response = await page.goto(url(path), { waitUntil: 'commit' });
  let status = response?.status() ?? 0;
  let text = await pageOrResponseText(page, response);

  for (const attempt of [1, 2]) {
    if (expectedText.test(text) || !/uhms-loading|<html/i.test(text)) {
      break;
    }

    await page.waitForTimeout(2_000 * attempt);
    response = await page.goto(url(path), { waitUntil: 'commit' });
    status = response?.status() ?? 0;
    text = await pageOrResponseText(page, response);
  }

  assertTextHasNoSensitiveLeak(text);
  test.skip(
    [404, 503].includes(status) && /not found|module|disabled|unavailable/i.test(text),
    `Report page ${path} is not available in this UHMS build.`,
  );

  expect(status).toBeLessThan(400);
  expect(text).toMatch(expectedText);

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

function createReportFixture() {
  reportFixture = runPhpJson<ReportFixture>(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reception = App\Models\User::where('email', getenv('UHMS_RECEPTION_EMAIL'))->firstOrFail();
$cashier = App\Models\User::where('email', getenv('UHMS_CASHIER_EMAIL'))->firstOrFail();

Illuminate\Support\Facades\Auth::login($reception);

foreach (['reports', 'billing', 'accounting_basic', 'accounting_advanced'] as $slug) {
    $module = App\Models\Module::where('slug', $slug)->first();
    if ($module) {
        $module->forceFill(['is_enabled' => true])->save();
    }
}

$department = App\Models\Department::updateOrCreate(
    ['code' => 'E2EREP'],
    [
        'name' => 'E2E Reports',
        'type' => App\Enums\DepartmentType::CONSULTATION->value,
        'status' => 'active',
    ]
);

$service = App\Models\ServiceCatalog::updateOrCreate(
    ['code' => 'E2E-REP-CON'],
    [
        'name' => 'E2E Report Consultation',
        'category' => App\Enums\ServiceType::CONSULTATION->value,
        'price' => 88.80,
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
    'first_name' => 'E2EReports' . $runId,
    'last_name' => 'CashPatient',
    'date_of_birth' => '1988-09-15',
    'gender' => App\Enums\Gender::MALE,
    'phone' => '026' . substr((string) now()->timestamp, -7),
    'email' => 'e2e.reports.' . $runId . '@example.test',
    'address' => 'E2E reports patient address',
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
    'chief_complaint' => 'E2E reports transaction check',
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

$invoice = $item->invoice->fresh(['items', 'payments', 'creditNotes']);
app(App\Services\InvoiceReceivableService::class)->syncFromInvoice($invoice);
$invoice = $invoice->fresh(['items', 'payments', 'patient']);

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
$payment = app(App\Services\PaymentService::class)->recordPayment($invoice, [
    'amount' => (float) $invoice->balance,
    'payment_method' => App\Enums\PaymentMethod::CASH->value,
    'reference_number' => 'E2E-REP-' . $runId,
    'notes' => 'E2E reports/payment visibility transaction.',
    'paid_at' => now(),
]);

$invoice = $invoice->fresh(['items', 'payments', 'patient']);
$payment = $payment->fresh(['invoice', 'patient']);

echo json_encode([
    'patientName' => $patient->full_name,
    'patientNumber' => $patient->patient_number,
    'serviceName' => $service->name,
    'invoiceId' => (string) $invoice->id,
    'invoiceNumber' => $invoice->invoice_number,
    'invoicePath' => route('admin.billing.invoices.show', $invoice, false),
    'paymentId' => (string) $payment->id,
    'paymentNumber' => $payment->payment_number,
    'paymentReference' => $payment->reference_number,
    'paymentPath' => route('admin.billing.payments.index', [
        'search' => $payment->payment_number,
        'date_from' => now()->toDateString(),
        'date_to' => now()->toDateString(),
    ], false),
    'receiptPath' => route('admin.billing.payments.receipt', $payment, false),
    'receiptPdfPath' => route('admin.billing.payments.receipt-pdf', $payment, false),
    'paymentDate' => now()->toDateString(),
    'totalAmount' => (float) $payment->amount,
], JSON_THROW_ON_ERROR);
`);

  return reportFixture;
}

function ensureReportFixture() {
  return reportFixture ?? createReportFixture();
}

test.describe('Level 10 reports, exports, and print workflow', () => {
  test('L10-REP-001 - Admin can open reports dashboard or reports module', async ({ page }) => {
    await loginAs(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    const { text } = await openPath(page, reportRoutes.dashboard, /report|dashboard|analytics|UHMS/i);

    expect(text).toMatch(/report|dashboard|analytics/i);
  });

  test('L10-REP-002 - Cashier can open daily payments or cashier report', async ({ page }) => {
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    const { text } = await openPath(page, reportRoutes.payments, /payment|collection|cashier|receipt|UHMS/i);

    expect(text).toMatch(/payment|collection|receipt/i);
  });

  test('L10-REP-003 - Accountant can open revenue or accounting report', async ({ page }) => {
    test.skip(!hasAccountantCredentials, 'UHMS_ACCOUNTANT_EMAIL and UHMS_ACCOUNTANT_PASSWORD are not configured.');

    await loginAs(page, 'UHMS_ACCOUNTANT_EMAIL', 'UHMS_ACCOUNTANT_PASSWORD');
    const { text } = await openPath(page, withDateRange(reportRoutes.accountingReport), /trial balance|accounting|debit|credit|UHMS/i);

    expect(text).toMatch(/trial balance|accounting|debit|credit/i);
  });

  test('L10-REP-004 - Date filter can be applied without errors', async ({ page }) => {
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    const { text } = await openPath(page, withDateRange(reportRoutes.payments), /payment|collection|total|UHMS/i);

    expect(page.url()).toContain('date_from=');
    expect(text).toMatch(/payment|total|collection/i);
  });

  test('L10-REP-005 - Report totals or rows are visible after filtering', async ({ page }) => {
    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    const { text } = await openPath(page, withDateRange(reportRoutes.payments), /total|payment|amount|UHMS/i);

    expect(text).toMatch(/total|amount|payment|collections?/i);
  });

  test('L10-REP-006 - Invoice/payment report includes an automated test transaction if available', async ({ page }) => {
    const fixture = ensureReportFixture();

    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    const { text } = await openPath(page, fixture.paymentPath, /payment|receipt|invoice|UHMS/i);

    expect(text).toContain(fixture.paymentNumber);
    expect(text).toContain(fixture.invoiceNumber);
    expect(text).toMatch(/E2EReports|E2E-REP|CashPatient/i);
  });

  test('L10-REP-007 - Print or preview button opens printable report view', async ({ page }) => {
    const fixture = ensureReportFixture();

    await loginAs(page, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    const { text } = await openPath(page, fixture.receiptPath, /receipt|payment|print|UHMS/i);

    expect(text).toContain(fixture.paymentNumber);
    expect(text).toMatch(/receipt|payment|print/i);
  });

  test('L10-REP-008 - Export button is available or export download starts if supported', async ({ page }) => {
    await loginAs(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
    await openPath(page, reportRoutes.income, /income|report|export|pdf|UHMS/i);

    const exportLink = page.locator('a[href*="export=pdf"], a:has-text("PDF"), a:has-text("Export")').first();
    try {
      await expect(exportLink).toBeVisible({ timeout: 20_000 });
    } catch {
      const downloadPromise = page.waitForEvent('download', { timeout: 20_000 }).catch(() => null);
      const response = await page.goto(url(reportRoutes.incomeExport), { waitUntil: 'commit' }).catch((error) => {
        if (/Download is starting/i.test(String(error))) {
          return null;
        }

        throw error;
      });
      const download = await downloadPromise;

      if (download) {
        expect(download.suggestedFilename()).toMatch(/\.pdf$/i);
        return;
      }

      const text = await pageOrResponseText(page, response);
      assertTextHasNoSensitiveLeak(text);
      expect(response?.status() ?? 0).toBeLessThan(500);
      return;
    }

    const href = await exportLink.getAttribute('href');
    expect(href ?? '').toMatch(/export=pdf|pdf/i);
  });

  test('L10-REP-009 - Limited user cannot access financial reports directly', async ({ browser }) => {
    const limitedPage = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      await assertPathBlockedForUser(limitedPage, reportRoutes.income, /income|total income|invoice register|financial/i);
    } finally {
      await limitedPage.close();
    }
  });

  test('L10-REP-010 - Receptionist cannot access accounting reports directly unless allowed', async ({ browser }) => {
    const receptionPage = await openNewLoggedInPage(browser, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

    try {
      await assertPathBlockedForUser(receptionPage, reportRoutes.accountingReport, /trial balance|accounting|ledger|debit|credit/i);
    } finally {
      await receptionPage.close();
    }
  });

  test('OTB-SEC-014 - Invalid report/export URL does not expose Laravel debug, SQL errors, stack traces, .env values, or file paths', async ({ page }) => {
    await loginAs(page, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');

    for (const path of [reportRoutes.invalidReport, reportRoutes.invalidExport, reportRoutes.invalidAccountingReport]) {
      const response = await page.goto(url(path), { waitUntil: 'commit' });
      const text = await pageOrResponseText(page, response);

      assertTextHasNoSensitiveLeak(text);
      expect(response?.status() ?? 0).not.toBe(500);
    }
  });
});

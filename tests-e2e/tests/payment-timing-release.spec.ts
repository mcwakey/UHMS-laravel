import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';
import { laravelRoot } from './support/laravel-root';

type Fixture = { visitId: number; invoiceId: number; receivableId: number; outstanding: string };
type Evidence = { clearance: string | null; exception: string | null; invoice: string; receivable: string; clinical: string };

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';
let fixture: Fixture;

function phpJson<T>(script: string): T {
  return JSON.parse(execFileSync(phpBinary, ['-r', script], {
    cwd: laravelRoot,
    env: process.env,
    stdio: 'pipe',
  }).toString('utf8')) as T;
}

function createFixture(): Fixture {
  return phpJson<Fixture>(String.raw`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$actor = App\Models\User::where('email', getenv('UHMS_FINANCE_EMAIL'))->firstOrFail();
$old = App\Models\Patient::withTrashed()->where('patient_number', 'E2E-FIN-CLEARANCE')->first();
if ($old) { $old->visits()->each(fn ($visit) => $visit->delete()); $old->forceDelete(); }

$patient = App\Models\Patient::create([
    'patient_number' => 'E2E-FIN-CLEARANCE', 'first_name' => 'E2E', 'last_name' => 'Financial Clearance',
    'date_of_birth' => '1990-01-01', 'gender' => App\Enums\Gender::MALE, 'phone' => '0200000000',
    'address' => 'Test only', 'city' => 'Accra', 'region' => 'Greater Accra', 'status' => 'active',
    'registered_by' => $actor->id,
]);
$visit = App\Models\Visit::create([
    'visit_number' => App\Models\Visit::generateVisitNumber(), 'patient_id' => $patient->id,
    'visit_type' => App\Enums\VisitType::OUTPATIENT, 'visit_date' => today(),
    'status' => App\Enums\VisitStatus::COMPLETED, 'priority' => App\Enums\Priority::NORMAL,
    'chief_complaint' => 'Release verification fixture', 'created_by' => $actor->id,
]);
$invoice = App\Models\Invoice::create([
    'invoice_number' => 'E2E-FIN-' . $visit->id, 'visit_id' => $visit->id, 'patient_id' => $patient->id,
    'billing_type' => App\Enums\BillingType::CASH, 'subtotal' => 150, 'total_amount' => 150,
    'amount_paid' => 50, 'balance' => 100, 'status' => App\Enums\InvoiceStatus::PARTIALLY_PAID,
    'created_by' => $actor->id,
]);
App\Models\InvoiceItem::create([
    'invoice_id' => $invoice->id, 'visit_id' => $visit->id, 'patient_id' => $patient->id,
    'description' => 'E2E conditional clearance service', 'quantity' => 1, 'unit_price' => 150,
    'selected_price' => 150, 'total_price' => 150, 'patient_payable' => 150,
    'paid_amount' => 50, 'balance' => 100, 'payment_status' => 'partially_paid',
]);
$receivable = App\Models\InvoiceReceivable::create([
    'invoice_id' => $invoice->id, 'visit_id' => $visit->id, 'patient_id' => $patient->id,
    'payer_type' => App\Models\InvoiceReceivable::PAYER_PATIENT, 'original_amount' => 150,
    'allocated_amount' => 150, 'paid_amount' => 50, 'balance' => 100,
    'aging_start_date' => today(), 'status' => App\Models\InvoiceReceivable::STATUS_PARTIALLY_PAID,
]);
App\Models\Setting::setValue('visit_financial_clearance', 'mode', 'active');
echo json_encode(['visitId' => $visit->id, 'invoiceId' => $invoice->id, 'receivableId' => $receivable->id, 'outstanding' => '100.00']);
`);
}

function evidence(): Evidence {
  return phpJson<Evidence>(String.raw`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$visit = App\Models\Visit::findOrFail(${fixture.visitId});
$clearance = $visit->financialClearance;
echo json_encode([
 'clearance' => $clearance?->status?->value,
 'exception' => $clearance?->currentException?->status?->value,
 'invoice' => (string) App\Models\Invoice::findOrFail(${fixture.invoiceId})->balance,
 'receivable' => (string) App\Models\InvoiceReceivable::findOrFail(${fixture.receivableId})->balance,
 'clinical' => $visit->status->value,
]);
`);
}

test.describe.configure({ mode: 'serial', timeout: 180_000 });

test.beforeAll(() => { ensurePermissionE2EUsers(); fixture = createFixture(); });
test.afterAll(() => {
  phpJson(String.raw`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\Setting::setValue('visit_financial_clearance', 'mode', 'disabled');
$patient = App\Models\Patient::withTrashed()->where('patient_number', 'E2E-FIN-CLEARANCE')->first();
if ($patient) { $patient->visits()->each(fn ($visit) => $visit->delete()); $patient->forceDelete(); }
echo json_encode(['ok' => true]);
`);
  cleanupPermissionE2EUsers();
});

test('conditional clearance preserves debt, clinical state, staleness, and rollback', async ({ page, browser }) => {
  await loginAs(page, 'UHMS_FINANCE_EMAIL', 'UHMS_FINANCE_PASSWORD');
  await page.goto(url(`/admin/billing/visit-financial-clearances/visit/${fixture.visitId}`));
  await page.getByRole('button', { name: 'Assess' }).click();
  await expect(page.locator('body')).toContainText('Pending');
  expect(evidence().clearance).toBe('pending');

  await page.locator('input[name="requested_amount"]').fill(fixture.outstanding);
  await page.locator('input[name="request_reason"]').fill('E2E approved outstanding balance');
  await page.getByRole('button', { name: 'Request', exact: true }).click();
  await page.locator('input[name="reason"]').last().fill('Requester must not self approve');
  await page.getByRole('button', { name: 'Approve', exact: true }).click();
  expect(evidence().exception).toBeNull();

  const approverPage = await browser.newPage();
  await loginAs(approverPage, 'UHMS_FINANCE_APPROVER_EMAIL', 'UHMS_FINANCE_APPROVER_PASSWORD');
  await approverPage.goto(url(`/admin/billing/visit-financial-clearances/visit/${fixture.visitId}`));
  await approverPage.locator('input[name="reason"]').last().fill('Separate finance approval');
  await approverPage.getByRole('button', { name: 'Approve', exact: true }).click();
  await expect(approverPage.locator('body')).toContainText('Conditionally cleared');

  await approverPage.locator('input[name="reason"]').first().fill('Release verification close');
  await approverPage.getByRole('button', { name: 'Financially Close Visit' }).click();
  await expect.poll(() => evidence().clearance, { timeout: 15_000 }).toBe('financially_closed');
  let state = evidence();
  expect(state).toMatchObject({ clearance: 'financially_closed', exception: 'approved', invoice: '100.00', receivable: '100.00', clinical: 'completed' });

  phpJson(String.raw`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$invoice = App\Models\Invoice::findOrFail(${fixture.invoiceId});
App\Models\InvoiceItem::create(['invoice_id'=>$invoice->id,'visit_id'=>$invoice->visit_id,'patient_id'=>$invoice->patient_id,'description'=>'E2E post-close charge','quantity'=>1,'unit_price'=>10,'selected_price'=>10,'total_price'=>10,'patient_payable'=>10,'paid_amount'=>0,'balance'=>10,'payment_status'=>'unpaid']);
echo json_encode(['ok'=>true]);
`);
  await expect.poll(() => evidence().clearance, { timeout: 15_000 }).toBe('stale');

  const adminPage = await browser.newPage();
  await loginAs(adminPage, 'UHMS_ADMIN_EMAIL', 'UHMS_ADMIN_PASSWORD');
  await adminPage.goto(url('/admin/billing/visit-financial-clearances/settings'));
  await adminPage.locator('form[action$="settings/rollback"] input[name="reason"]').fill('E2E rollback proof');
  await adminPage.getByRole('button', { name: 'Return to disabled' }).click();
  await expect(adminPage.locator('body')).toContainText('Effective mode: disabled');
  state = evidence();
  expect(state).toMatchObject({ clearance: 'stale', exception: 'approved', invoice: '100.00', receivable: '100.00', clinical: 'completed' });
});

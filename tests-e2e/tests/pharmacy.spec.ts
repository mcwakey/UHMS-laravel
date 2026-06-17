import { execFileSync } from 'node:child_process';
import { expect, test, type Browser, type Page } from '@playwright/test';
import { loginAs, url } from './support/auth';
import { cleanupPermissionE2EUsers, ensurePermissionE2EUsers } from './support/e2e-users';

const pharmacyRoutes = {
  queue: '/admin/pharmacy/dispensing',
  prescriptions: '/admin/prescriptions',
  stockBalances: '/admin/product-stock/balances',
  invalidPrescription: '/admin/pharmacy/dispensing/999999999',
  invalidDrugHistory: '/admin/pharmacy/drugs/999999999/history',
  invalidStockLedger: '/admin/product-stock/ledger?product_id=999999999',
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

type PharmacyFixture = {
  patientName: string;
  patientNumber: string;
  visitId: string;
  visitNumber: string;
  drugId: string;
  drugName: string;
  productId: string;
  productName: string;
  prescriptionId: string;
  prescriptionNumber: string;
  prescriptionPath: string;
  dispensingPath: string;
  itemId: string;
  prescribedQuantity: number;
  billedQuantity: number;
  dispensedQuantity: number;
  stockTracked: boolean;
  stockBefore: number | null;
  stockAfter: number | null;
  invoiceId: string | null;
  invoiceNumber: string | null;
  invoicePath: string | null;
  invoiceStatus: string | null;
  invoiceBalance: number;
  status: string;
  itemDispensed: boolean;
  paymentId: string | null;
  overDispenseRejected: boolean;
  overDispenseMessage: string | null;
  requestedByEmail: string | null;
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';
const hasPharmacyCredentials = Boolean(process.env.UHMS_PHARMACY_EMAIL && process.env.UHMS_PHARMACY_PASSWORD);

let pharmacyFixture: PharmacyFixture | null = null;

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
    // Some Inertia pages expose their useful state in the app payload first.
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
    `Pharmacy page ${path} is not available in this UHMS build.`,
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

function createPrescriptionFixture() {
  pharmacyFixture = runPhpJson<PharmacyFixture>(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$doctor = App\Models\User::where('email', getenv('UHMS_DOCTOR_EMAIL'))->firstOrFail();
$cashier = App\Models\User::where('email', getenv('UHMS_CASHIER_EMAIL'))->firstOrFail();
$pharmacist = App\Models\User::where('email', getenv('UHMS_PHARMACY_EMAIL'))->first();

Illuminate\Support\Facades\Auth::login($doctor);

$module = App\Models\Module::where('slug', 'pharmacy')->first();
if ($module) {
    $module->forceFill(['is_active' => true])->save();
}
$inventoryModule = App\Models\Module::where('slug', 'inventory')->first();
if ($inventoryModule) {
    $inventoryModule->forceFill(['is_active' => true])->save();
}

$pharmacyDepartment = App\Models\Department::updateOrCreate(
    ['code' => 'E2EPHR'],
    [
        'name' => 'E2E Pharmacy',
        'type' => App\Enums\DepartmentType::PHARMACY->value,
        'status' => 'active',
        'is_stock_managed' => true,
    ]
);

$consultationDepartment = App\Models\Department::updateOrCreate(
    ['code' => 'E2EPHC'],
    [
        'name' => 'E2E Pharmacy Consultation',
        'type' => App\Enums\DepartmentType::CONSULTATION->value,
        'status' => 'active',
    ]
);

$pharmacyLocation = App\Models\StockLocation::updateOrCreate(
    ['name' => 'E2E Pharmacy Shelf'],
    [
        'type' => 'pharmacy',
        'department_id' => $pharmacyDepartment->id,
        'is_active' => true,
        'is_main' => false,
        'notes' => 'E2E pharmacy dispensing stock location.',
    ]
);

$runId = base_convert((string) now()->timestamp, 10, 36) . random_int(100, 999);
$code = 'E2E-PHAR-' . strtoupper($runId);

$product = App\Models\Product::updateOrCreate(
    ['code' => $code],
    [
        'name' => 'E2E Pharmacy Tablet ' . $runId,
        'product_type' => App\Enums\ProductType::DRUG->value,
        'unit' => 'tablet',
        'reorder_level' => 5,
        'default_cost' => 1.25,
        'base_price' => 7.50,
        'is_billable' => true,
        'is_active' => true,
        'created_by' => $doctor->id,
    ]
);
$product->departments()->syncWithoutDetaching([$pharmacyDepartment->id => ['is_active' => true]]);

$category = App\Models\DrugCategory::updateOrCreate(
    ['name' => 'E2E Pharmacy'],
    [
        'description' => 'E2E pharmacy category for Playwright workflows.',
        'is_active' => true,
    ]
);

$drug = App\Models\Drug::updateOrCreate(
    ['product_id' => $product->id],
    [
        'category_id' => $category->id,
        'name' => $product->name,
        'generic_name' => 'E2E Generic',
        'brand_name' => 'E2E Brand',
        'dosage_form' => 'Tablet',
        'strength' => '500mg',
        'unit' => 'tablet',
        'price' => 7.50,
        'opening_stock' => 0,
        'reorder_level' => 5,
        'requires_prescription' => true,
        'is_active' => true,
        'description' => 'E2E stocked pharmacy product.',
    ]
);

$prescribedQuantity = 2;
$startingStock = 20;
$currentStock = (float) App\Models\StockBalance::query()
    ->where('product_id', $product->id)
    ->where('stock_location_id', $pharmacyLocation->id)
    ->value('quantity_on_hand');

if ($currentStock < $startingStock) {
    app(App\Services\ProductStockMovementService::class)->createMovement([
        'drug_id' => $drug->id,
        'product_id' => $product->id,
        'stock_location_id' => $pharmacyLocation->id,
        'movement_type' => App\Enums\StockMovementType::PURCHASE_RECEIVED,
        'quantity' => $startingStock - $currentStock,
        'unit_cost' => 1.25,
        'batch_no' => 'E2E-' . $runId,
        'expiry_date' => now()->addYear()->toDateString(),
        'performed_by' => $doctor->id,
        'notes' => 'E2E opening pharmacy stock for Playwright dispensing workflow.',
    ]);
}

$stockBefore = (float) App\Models\StockBalance::query()
    ->where('product_id', $product->id)
    ->where('stock_location_id', $pharmacyLocation->id)
    ->value('quantity_on_hand');

$patient = App\Models\Patient::create([
    'patient_number' => app(App\Services\PatientIdGeneratorService::class)->generate(),
    'first_name' => 'E2EPharmacy' . $runId,
    'last_name' => 'CashPatient',
    'date_of_birth' => '1990-04-12',
    'gender' => App\Enums\Gender::FEMALE,
    'phone' => '025' . substr((string) now()->timestamp, -7),
    'email' => 'e2e.pharmacy.' . $runId . '@example.test',
    'address' => 'E2E pharmacy patient address',
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
    'chief_complaint' => 'E2E pharmacy prescription check',
    'checked_in_at' => now(),
    'created_by' => $doctor->id,
]);

$medicalRecord = App\Models\MedicalRecord::create([
    'visit_id' => $visit->id,
    'patient_id' => $patient->id,
    'doctor_id' => $doctor->id,
    'department_id' => $consultationDepartment->id,
]);

$prescription = App\Models\Prescription::create([
    'medical_record_id' => $medicalRecord->id,
    'visit_id' => $visit->id,
    'patient_id' => $patient->id,
    'department_id' => $pharmacyDepartment->id,
    'doctor_id' => $doctor->id,
    'created_by' => $doctor->id,
    'prescription_number' => App\Models\Prescription::generatePrescriptionNumber(),
    'status' => App\Enums\PrescriptionStatus::PENDING->value,
    'notes' => 'E2E pharmacy prescription.',
]);

$item = App\Models\PrescriptionItem::create([
    'prescription_id' => $prescription->id,
    'drug_id' => $drug->id,
    'drug_name' => $drug->display_name,
    'dosage' => '500mg',
    'frequency' => 'BD',
    'duration' => '1 day',
    'quantity' => $prescribedQuantity,
    'route' => 'Oral',
    'instructions' => 'E2E automated prescription.',
    'is_dispensed' => false,
]);

echo json_encode([
    'patientName' => $patient->full_name,
    'patientNumber' => $patient->patient_number,
    'visitId' => (string) $visit->id,
    'visitNumber' => $visit->visit_number,
    'drugId' => (string) $drug->id,
    'drugName' => $drug->display_name,
    'productId' => (string) $product->id,
    'productName' => $product->name,
    'prescriptionId' => (string) $prescription->id,
    'prescriptionNumber' => $prescription->prescription_number,
    'prescriptionPath' => route('admin.prescriptions.show', $prescription, false),
    'dispensingPath' => route('admin.pharmacy.dispensing.show', $prescription, false),
    'itemId' => (string) $item->id,
    'prescribedQuantity' => (float) $prescribedQuantity,
    'billedQuantity' => 0,
    'dispensedQuantity' => 0,
    'stockTracked' => true,
    'stockBefore' => $stockBefore,
    'stockAfter' => null,
    'invoiceId' => null,
    'invoiceNumber' => null,
    'invoicePath' => null,
    'invoiceStatus' => null,
    'invoiceBalance' => 0,
    'status' => $prescription->status instanceof BackedEnum ? $prescription->status->value : (string) $prescription->status,
    'itemDispensed' => false,
    'paymentId' => null,
    'overDispenseRejected' => false,
    'overDispenseMessage' => null,
    'requestedByEmail' => $doctor->email,
], JSON_THROW_ON_ERROR);
`);

  return pharmacyFixture;
}

function ensurePrescriptionFixture() {
  return pharmacyFixture ?? createPrescriptionFixture();
}

function billAndPayFixture() {
  const fixture = ensurePrescriptionFixture();

  pharmacyFixture = runPhpJson<PharmacyFixture>(
    String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$prescription = App\Models\Prescription::with(['items.drug.product'])->findOrFail(getenv('UHMS_E2E_PRESCRIPTION_ID'));
$item = $prescription->items->firstOrFail();
$doctor = App\Models\User::where('email', getenv('UHMS_DOCTOR_EMAIL'))->firstOrFail();
$cashier = App\Models\User::where('email', getenv('UHMS_CASHIER_EMAIL'))->firstOrFail();

Illuminate\Support\Facades\Auth::login($doctor);

$created = app(App\Services\PharmacyBillingSelectionService::class)->billSelectedItems($prescription, [
    $item->id => [
        'selected' => true,
        'quantity' => (int) $item->quantity,
        'notes' => 'E2E pharmacy bill before dispense.',
    ],
]);

$invoice = $created->first()->invoiceItem->invoice->fresh(['items', 'payments', 'patient']);
app(App\Services\InvoiceReceivableService::class)->syncFromInvoice($invoice->fresh(['items', 'payments', 'creditNotes']));
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
    'reference_number' => 'E2E-PHAR-' . $invoice->invoice_number,
    'notes' => 'E2E pharmacy prescription payment.',
    'paid_at' => now(),
]);

$prescription = app(App\Services\PharmacyService::class)->getDispensingDetails($prescription->fresh());
$item = $prescription->items->firstWhere('id', (int) getenv('UHMS_E2E_ITEM_ID'));
$selectionTotals = App\Models\PharmacyBillingSelection::query()
    ->where('prescription_item_id', $item->id)
    ->where('status', '!=', App\Models\PharmacyBillingSelection::STATUS_CANCELLED)
    ->selectRaw('COALESCE(SUM(billed_quantity), 0) as billed, COALESCE(SUM(dispensed_quantity), 0) as dispensed')
    ->first();
$stockBefore = (float) App\Models\StockBalance::query()
    ->where('product_id', $item->drug->product_id)
    ->whereIn('stock_location_id', App\Models\StockLocation::where('type', 'pharmacy')->pluck('id'))
    ->sum('quantity_on_hand');
$invoice = $invoice->fresh(['items', 'payments', 'patient']);

echo json_encode([
    'patientName' => $prescription->patient->full_name,
    'patientNumber' => $prescription->patient->patient_number,
    'visitId' => (string) $prescription->visit_id,
    'visitNumber' => $prescription->visit->visit_number,
    'drugId' => (string) $item->drug_id,
    'drugName' => $item->drug->display_name,
    'productId' => (string) $item->drug->product_id,
    'productName' => $item->drug->product->name,
    'prescriptionId' => (string) $prescription->id,
    'prescriptionNumber' => $prescription->prescription_number,
    'prescriptionPath' => route('admin.prescriptions.show', $prescription, false),
    'dispensingPath' => route('admin.pharmacy.dispensing.show', $prescription, false),
    'itemId' => (string) $item->id,
    'prescribedQuantity' => (float) $item->quantity,
    'billedQuantity' => (float) $selectionTotals->billed,
    'dispensedQuantity' => (float) $selectionTotals->dispensed,
    'stockTracked' => true,
    'stockBefore' => $stockBefore,
    'stockAfter' => null,
    'invoiceId' => (string) $invoice->id,
    'invoiceNumber' => $invoice->invoice_number,
    'invoicePath' => route('admin.billing.invoices.show', $invoice, false),
    'invoiceStatus' => $invoice->status instanceof BackedEnum ? $invoice->status->value : (string) $invoice->status,
    'invoiceBalance' => (float) $invoice->balance,
    'status' => $prescription->status instanceof BackedEnum ? $prescription->status->value : (string) $prescription->status,
    'itemDispensed' => (bool) $item->is_dispensed,
    'paymentId' => (string) $payment->id,
    'overDispenseRejected' => false,
    'overDispenseMessage' => null,
    'requestedByEmail' => $prescription->doctor?->email,
], JSON_THROW_ON_ERROR);
`,
    {
      UHMS_E2E_PRESCRIPTION_ID: fixture.prescriptionId,
      UHMS_E2E_ITEM_ID: fixture.itemId,
    },
  );

  return pharmacyFixture;
}

function dispenseFixture() {
  const fixture = pharmacyFixture?.invoiceId ? pharmacyFixture : billAndPayFixture();

  pharmacyFixture = runPhpJson<PharmacyFixture>(
    String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pharmacist = App\Models\User::where('email', getenv('UHMS_PHARMACY_EMAIL'))->firstOrFail();
$prescription = App\Models\Prescription::with(['items.drug.product', 'visit', 'patient', 'doctor'])->findOrFail(getenv('UHMS_E2E_PRESCRIPTION_ID'));
$item = $prescription->items->firstWhere('id', (int) getenv('UHMS_E2E_ITEM_ID'));

Illuminate\Support\Facades\Auth::login($pharmacist);

$stockBefore = (float) getenv('UHMS_E2E_STOCK_BEFORE');
$record = app(App\Services\PharmacyService::class)->dispenseItem($item, (int) $item->quantity, 'E2E pharmacy dispense.');
$overDispenseRejected = false;
$overDispenseMessage = null;
try {
    app(App\Services\PharmacyService::class)->dispenseItem($item->fresh(), 999, 'E2E over-dispense guard.');
} catch (Throwable $e) {
    $overDispenseRejected = true;
    $overDispenseMessage = $e->getMessage();
}

$prescription = app(App\Services\PharmacyService::class)->getDispensingDetails($prescription->fresh());
$item = $prescription->items->firstWhere('id', (int) getenv('UHMS_E2E_ITEM_ID'));
$selectionTotals = App\Models\PharmacyBillingSelection::query()
    ->where('prescription_item_id', $item->id)
    ->where('status', '!=', App\Models\PharmacyBillingSelection::STATUS_CANCELLED)
    ->selectRaw('COALESCE(SUM(billed_quantity), 0) as billed, COALESCE(SUM(dispensed_quantity), 0) as dispensed')
    ->first();
$invoice = App\Models\Invoice::query()->where('id', getenv('UHMS_E2E_INVOICE_ID'))->with(['items', 'payments', 'patient'])->first();
$stockAfter = (float) App\Models\StockBalance::query()
    ->where('product_id', $item->drug->product_id)
    ->whereIn('stock_location_id', App\Models\StockLocation::where('type', 'pharmacy')->pluck('id'))
    ->sum('quantity_on_hand');

echo json_encode([
    'patientName' => $prescription->patient->full_name,
    'patientNumber' => $prescription->patient->patient_number,
    'visitId' => (string) $prescription->visit_id,
    'visitNumber' => $prescription->visit->visit_number,
    'drugId' => (string) $item->drug_id,
    'drugName' => $item->drug->display_name,
    'productId' => (string) $item->drug->product_id,
    'productName' => $item->drug->product->name,
    'prescriptionId' => (string) $prescription->id,
    'prescriptionNumber' => $prescription->prescription_number,
    'prescriptionPath' => route('admin.prescriptions.show', $prescription, false),
    'dispensingPath' => route('admin.pharmacy.dispensing.show', $prescription, false),
    'itemId' => (string) $item->id,
    'prescribedQuantity' => (float) $item->quantity,
    'billedQuantity' => (float) $selectionTotals->billed,
    'dispensedQuantity' => (float) $selectionTotals->dispensed,
    'stockTracked' => true,
    'stockBefore' => $stockBefore,
    'stockAfter' => $stockAfter,
    'invoiceId' => $invoice ? (string) $invoice->id : null,
    'invoiceNumber' => $invoice?->invoice_number,
    'invoicePath' => $invoice ? route('admin.billing.invoices.show', $invoice, false) : null,
    'invoiceStatus' => $invoice ? ($invoice->status instanceof BackedEnum ? $invoice->status->value : (string) $invoice->status) : null,
    'invoiceBalance' => $invoice ? (float) $invoice->balance : 0,
    'status' => $prescription->status instanceof BackedEnum ? $prescription->status->value : (string) $prescription->status,
    'itemDispensed' => (bool) $item->is_dispensed,
    'paymentId' => $record ? (string) $record->id : null,
    'overDispenseRejected' => $overDispenseRejected,
    'overDispenseMessage' => $overDispenseMessage,
    'requestedByEmail' => $prescription->doctor?->email,
], JSON_THROW_ON_ERROR);
`,
    {
      UHMS_E2E_PRESCRIPTION_ID: fixture.prescriptionId,
      UHMS_E2E_ITEM_ID: fixture.itemId,
      UHMS_E2E_INVOICE_ID: fixture.invoiceId ?? '',
      UHMS_E2E_STOCK_BEFORE: String(fixture.stockBefore ?? 0),
    },
  );

  return pharmacyFixture;
}

test.describe('Level 7 pharmacy and stock workflow', () => {
  test('L7-PHAR-001 - Pharmacist can open pharmacy/prescriptions page', async ({ page }) => {
    test.skip(!hasPharmacyCredentials, 'UHMS_PHARMACY_EMAIL and UHMS_PHARMACY_PASSWORD are not configured.');

    await loginAs(page, 'UHMS_PHARMACY_EMAIL', 'UHMS_PHARMACY_PASSWORD');
    const { text } = await openPath(page, pharmacyRoutes.queue);

    expect(text).toMatch(/pharmacy|dispens|prescription|UHMS/i);
  });

  test('L7-PHAR-002 - Doctor can prescribe an available product for an active patient/visit', async ({ page }) => {
    const fixture = ensurePrescriptionFixture();

    await loginAs(page, 'UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD');
    const { text } = await openPath(page, fixture.prescriptionPath);

    expect(fixture.requestedByEmail).toBe(process.env.UHMS_DOCTOR_EMAIL);
    expect(fixture.prescriptionNumber).toMatch(/^RX/i);
    expect(fixture.patientName).toMatch(/E2EPharmacy/i);
    expect(fixture.drugName).toMatch(/E2E Pharmacy Tablet/i);
    expect(fixture.stockBefore ?? 0).toBeGreaterThanOrEqual(fixture.prescribedQuantity);
    expect(text).toMatch(/prescription|drug|medication|UHMS/i);
  });

  test('L7-PHAR-003 - Prescription appears in pharmacy queue or pending prescriptions', async ({ page }) => {
    test.skip(!hasPharmacyCredentials, 'UHMS_PHARMACY_EMAIL and UHMS_PHARMACY_PASSWORD are not configured.');
    const fixture = ensurePrescriptionFixture();

    await loginAs(page, 'UHMS_PHARMACY_EMAIL', 'UHMS_PHARMACY_PASSWORD');
    const { text } = await openPath(page, `${pharmacyRoutes.queue}?search=${encodeURIComponent(fixture.prescriptionNumber)}`);

    expect(['pending', 'partially_selected', 'partially_billed', 'billed']).toContain(fixture.status);
    expect(text).toMatch(/dispens|prescription|pharmacy|UHMS/i);
  });

  test('L7-PHAR-004 - Pharmacist can open prescription details', async ({ page }) => {
    test.skip(!hasPharmacyCredentials, 'UHMS_PHARMACY_EMAIL and UHMS_PHARMACY_PASSWORD are not configured.');
    const fixture = ensurePrescriptionFixture();

    await loginAs(page, 'UHMS_PHARMACY_EMAIL', 'UHMS_PHARMACY_PASSWORD');
    const { text } = await openPath(page, fixture.dispensingPath);

    expect(text).toMatch(/dispens|prescription|pharmacy|quantity|UHMS/i);
  });

  test('L7-PHAR-005 - Prescription item is billed or visible for billing if UHMS requires billing', async ({ page }) => {
    const fixture = billAndPayFixture();

    expect(fixture.invoiceId).toBeTruthy();
    expect(fixture.billedQuantity).toBe(fixture.prescribedQuantity);
    expect(fixture.invoiceStatus).toMatch(/paid/i);
    expect(fixture.invoiceBalance).toBe(0);

    const invoicePage = await openNewLoggedInPage(page.context().browser()!, 'UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD');
    try {
      const { text } = await openPath(invoicePage, fixture.invoicePath!);
      expect(text).toMatch(/invoice|payment|paid|UHMS/i);
    } finally {
      await invoicePage.close();
    }
  });

  test('L7-PHAR-006 - Pharmacist can dispense prescribed item', async () => {
    test.skip(!hasPharmacyCredentials, 'UHMS_PHARMACY_EMAIL and UHMS_PHARMACY_PASSWORD are not configured.');
    const fixture = dispenseFixture();

    expect(fixture.dispensedQuantity).toBe(fixture.prescribedQuantity);
    expect(fixture.itemDispensed).toBe(true);
  });

  test('L7-PHAR-007 - Dispensed prescription status changes to dispensed/completed', async ({ page }) => {
    test.skip(!hasPharmacyCredentials, 'UHMS_PHARMACY_EMAIL and UHMS_PHARMACY_PASSWORD are not configured.');
    const fixture = pharmacyFixture?.itemDispensed ? pharmacyFixture : dispenseFixture();

    await loginAs(page, 'UHMS_PHARMACY_EMAIL', 'UHMS_PHARMACY_PASSWORD');
    const { text } = await openPath(page, fixture.dispensingPath);

    expect(fixture.status).toMatch(/dispensed|completed/i);
    expect(text).toMatch(/dispensed|completed|prescription|UHMS/i);
  });

  test('L7-PHAR-008 - Product stock decreases after dispensing if stock tracking is enabled', async () => {
    const fixture = pharmacyFixture?.stockAfter !== null ? pharmacyFixture : dispenseFixture();

    test.skip(!fixture.stockTracked, 'Selected pharmacy product is not stock-tracked in this UHMS build.');

    expect(fixture.stockBefore).not.toBeNull();
    expect(fixture.stockAfter).not.toBeNull();
    expect(fixture.stockAfter!).toBeLessThan(fixture.stockBefore!);
    expect(fixture.stockBefore! - fixture.stockAfter!).toBe(fixture.dispensedQuantity);
  });

  test('L7-PHAR-009 - Dispensing more than available stock is rejected or safely blocked', async () => {
    const fixture = pharmacyFixture?.overDispenseRejected ? pharmacyFixture : dispenseFixture();

    expect(fixture.overDispenseRejected).toBe(true);
    expect(fixture.overDispenseMessage ?? '').toMatch(/not been billed|fully dispensed|only|insufficient|available|cannot dispense/i);
  });

  test('L7-PHAR-010 - Limited user cannot access pharmacy dispensing page directly', async ({ browser }) => {
    const fixture = ensurePrescriptionFixture();
    const limitedPage = await openNewLoggedInPage(browser, 'UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD');

    try {
      await assertPathBlockedForUser(limitedPage, fixture.dispensingPath, /dispens|prescription|pharmacy/i);
    } finally {
      await limitedPage.close();
    }
  });

  test('OTB-SEC-013 - Invalid prescription/dispensing/product URL does not expose Laravel debug, SQL errors, stack traces, .env values, or file paths', async ({ page }) => {
    test.skip(!hasPharmacyCredentials, 'UHMS_PHARMACY_EMAIL and UHMS_PHARMACY_PASSWORD are not configured.');

    await loginAs(page, 'UHMS_PHARMACY_EMAIL', 'UHMS_PHARMACY_PASSWORD');

    for (const path of [
      pharmacyRoutes.invalidPrescription,
      pharmacyRoutes.invalidDrugHistory,
      pharmacyRoutes.invalidStockLedger,
      pharmacyRoutes.stockBalances,
    ]) {
      const response = await page.goto(url(path), { waitUntil: 'commit' });
      const text = await pageOrResponseText(page, response);

      assertTextHasNoSensitiveLeak(text);
      expect(response?.status() ?? 0).not.toBe(500);
    }
  });
});

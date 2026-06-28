<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Lights up the department-type showcase dashboards with realistic, department-scoped
 * demo data (stock balances, visits, invoices, prescriptions) over the last 7 days,
 * so KPI sparklines, trend charts, queues and donuts actually have something to draw.
 *
 * Re-runnable and best-effort: each section is guarded so a failure in one domain
 * does not abort the rest. Pure demo data — safe to run only in dev.
 */
class DepartmentShowcaseDemoSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->orderBy('id')->first();
        if (! $creator) {
            $this->command?->warn('No users found — run RoleSeeder/DemoUserSeeder first.');

            return;
        }

        $patients = Patient::query()->inRandomOrder()->limit(25)->get();
        if ($patients->isEmpty()) {
            $patients = Patient::factory()->count(15)->create();
        }

        // One representative department per type (data is keyed by department type).
        $clinical = $this->oneDepartmentPerType(['consultation', 'emergency', 'investigation', 'radiology', 'theatre', 'inpatient', 'maternity', 'records', 'finance']);
        $stock = $this->oneDepartmentPerType(['pharmacy', 'stores', 'blood_bank']);

        $summary = [];
        $summary[] = $this->seedStock($stock);
        $summary[] = $this->seedVisits($clinical->values(), $patients, $creator);
        $summary[] = $this->seedInvoices($clinical->values()->merge($stock->values()), $patients, $creator);
        $summary[] = $this->seedPrescriptions($stock->get('pharmacy'), $patients, $creator);

        $this->command?->info('Showcase demo data: '.implode(' · ', array_filter($summary)));
    }

    /** First department of each given type, keyed by type. */
    private function oneDepartmentPerType(array $types)
    {
        return collect($types)->mapWithKeys(function (string $type) {
            $dept = Department::where('type', $type)->orderBy('id')->first();

            return $dept ? [$type => $dept] : [];
        });
    }

    /** Stock balances (some below reorder) so Low Stock, the stock donut and stock usage light up. */
    private function seedStock($stockDepts): string
    {
        try {
            $products = Product::query()->where('is_active', true)->limit(12)->get();
            if ($products->isEmpty()) {
                return 'stock: no products';
            }

            $typeFor = ['pharmacy' => 'pharmacy', 'stores' => 'store', 'blood_bank' => 'store'];
            $count = 0;

            foreach ($stockDepts as $type => $dept) {
                $location = StockLocation::firstOrCreate(
                    ['department_id' => $dept->id],
                    ['name' => $dept->name.' Store', 'type' => $typeFor[$type] ?? 'store', 'is_active' => true, 'is_main' => false]
                );

                foreach ($products as $i => $product) {
                    $reorder = max((int) $product->reorder_level, 20);
                    if ((int) $product->reorder_level <= 0) {
                        $product->forceFill(['reorder_level' => $reorder])->save();
                    }
                    // ~1 in 3 below reorder → "low stock".
                    $qty = $i % 3 === 0 ? random_int(0, max(0, $reorder - 1)) : random_int($reorder + 10, $reorder + 250);
                    $cost = (float) ($product->default_cost ?? 1);

                    StockBalance::updateOrCreate(
                        ['product_id' => $product->id, 'stock_location_id' => $location->id],
                        ['quantity_on_hand' => $qty, 'average_cost' => $cost, 'total_value' => $qty * $cost, 'last_movement_at' => now()->subDays(random_int(0, 6))]
                    );
                    $count++;
                }
            }

            return "stock: {$count} balances";
        } catch (Throwable $e) {
            return 'stock FAILED: '.$e->getMessage();
        }
    }

    /** Visits across the trailing 7 days, scoped per clinical department. */
    private function seedVisits($departments, $patients, User $creator): string
    {
        try {
            $statuses = ['queued', 'waiting', 'consulting', 'triage', 'completed'];
            $count = 0;

            foreach ($departments as $dept) {
                for ($d = 6; $d >= 0; $d--) {
                    foreach (range(1, random_int(1, 4)) as $ignored) {
                        Visit::factory()->create([
                            'current_department_id' => $dept->id,
                            'patient_id' => $patients->random()->id,
                            'created_by' => $creator->id,
                            'status' => $statuses[array_rand($statuses)],
                            'created_at' => now()->subDays($d)->setTime(random_int(8, 16), random_int(0, 59)),
                        ]);
                        $count++;
                    }
                }
            }

            return "visits: {$count}";
        } catch (Throwable $e) {
            return 'visits FAILED: '.$e->getMessage();
        }
    }

    /** Invoices + items over 7 days so the activity trend area chart and activity counts populate. */
    private function seedInvoices($departments, $patients, User $creator): string
    {
        try {
            $items = 0;

            foreach ($departments as $dept) {
                for ($d = 6; $d >= 0; $d--) {
                    $patient = $patients->random();
                    $when = now()->subDays($d)->setTime(random_int(8, 16), random_int(0, 59));
                    $lineTotal = 0;
                    $lines = [];

                    foreach (range(1, random_int(1, 3)) as $ignored) {
                        $amount = random_int(20, 400);
                        $lineTotal += $amount;
                        $lines[] = ['amount' => $amount];
                    }

                    $invoice = Invoice::create([
                        'invoice_number' => 'DMO-'.$dept->code.'-'.$when->format('ymd').'-'.random_int(1000, 9999),
                        'patient_id' => $patient->id,
                        'billing_type' => 'cash',
                        'status' => 'paid',
                        'subtotal' => $lineTotal,
                        'total_amount' => $lineTotal,
                        'amount_paid' => $lineTotal,
                        'balance' => 0,
                        'created_by' => $creator->id,
                        'created_at' => $when,
                        'updated_at' => $when,
                    ]);

                    foreach ($lines as $line) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'patient_id' => $patient->id,
                            'department_id' => $dept->id,
                            'description' => $dept->name.' service',
                            'quantity' => 1,
                            'unit_price' => $line['amount'],
                            'total_price' => $line['amount'],
                            'patient_payable' => $line['amount'],
                            'paid_amount' => $line['amount'],
                            'balance' => 0,
                            'payment_status' => 'paid',
                            'created_by' => $creator->id,
                            'created_at' => $when,
                            'updated_at' => $when,
                        ]);
                        $items++;
                    }
                }
            }

            return "invoice_items: {$items}";
        } catch (Throwable $e) {
            return 'invoices FAILED: '.$e->getMessage();
        }
    }

    /** Prescriptions (pending + dispensed today) for the pharmacy KPIs and queue. */
    private function seedPrescriptions(?Department $pharmacy, $patients, User $creator): string
    {
        try {
            // prescriptions.medical_record_id and visit_id are NOT NULL → reuse existing rows.
            $recordIds = \Illuminate\Support\Facades\DB::table('medical_records')->inRandomOrder()->limit(10)->pluck('id')->all();
            $visitIds = \Illuminate\Support\Facades\DB::table('visits')->inRandomOrder()->limit(10)->pluck('id')->all();
            if ($recordIds === [] || $visitIds === []) {
                return 'prescriptions: skipped (no medical_records/visits)';
            }

            $count = 0;
            foreach (range(1, 10) as $n) {
                $dispensed = $n > 6;
                Prescription::create(array_filter([
                    'prescription_number' => 'RX'.now()->format('ymd').str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                    'medical_record_id' => $recordIds[array_rand($recordIds)],
                    'visit_id' => $visitIds[array_rand($visitIds)],
                    'patient_id' => $patients->random()->id,
                    'doctor_id' => $creator->id,
                    'department_id' => $pharmacy?->id,
                    'created_by' => $creator->id,
                    'status' => $dispensed ? 'dispensed' : 'pending',
                    'created_at' => now()->subDays(random_int(0, 6)),
                    'updated_at' => $dispensed ? today()->setTime(10, 0) : now(),
                ], fn ($v) => $v !== null));
                $count++;
            }

            return "prescriptions: {$count}";
        } catch (Throwable $e) {
            return 'prescriptions FAILED: '.$e->getMessage();
        }
    }
}

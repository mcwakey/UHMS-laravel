<?php

namespace App\Console\Commands;

use App\Enums\DepartmentType;
use App\Enums\ProductType;
use App\Models\Department;
use App\Models\Drug;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * inventory:link-drugs-to-products
 *
 * For each Drug that does not yet have an associated Product, this command
 * creates a Product (product_type = DRUG) carrying the drug's identity
 * (name, unit, base_price = drug.price, reorder_level) and links
 * drugs.product_id to it. It also attaches the new product to the Pharmacy
 * department via the product_department pivot so dispensing UIs can see it.
 *
 * Idempotent: drugs that already have product_id are skipped.
 */
class LinkDrugsToProductsCommand extends Command
{
    protected $signature = 'inventory:link-drugs-to-products
                            {--dry-run : Show what would happen without writing}
                            {--drug= : Only link a single drug by id}';

    protected $description = 'Backfill a Product per Drug and set drugs.product_id (Phase 2 unified inventory).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $only = $this->option('drug') ? (int) $this->option('drug') : null;

        $pharmacyDept = Department::query()
            ->where('type', DepartmentType::PHARMACY->value)
            ->orWhere('name', 'Pharmacy')
            ->orderBy('id')
            ->first();

        if (! $pharmacyDept) {
            $this->warn('No Pharmacy department found — products will be created but not linked to a department.');
        }

        $query = Drug::query()->whereNull('product_id');
        if ($only) {
            $query->whereKey($only);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('All drugs are already linked to products.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Linking %d drug(s) to products%s...', $total, $dry ? ' (dry-run)' : ''));

        $linked = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(100, function ($drugs) use (&$linked, &$skipped, $dry, $pharmacyDept) {
            foreach ($drugs as $drug) {
                /** @var Drug $drug */
                try {
                    $code = $this->buildProductCode($drug);

                    if ($dry) {
                        $this->line(sprintf('  [dry-run] would create product code=%s for drug #%d (%s)', $code, $drug->id, $drug->name));
                        $linked++;
                        continue;
                    }

                    DB::transaction(function () use ($drug, $code, $pharmacyDept, &$linked) {
                        $product = Product::query()
                            ->where('code', $code)
                            ->first();

                        if (! $product) {
                            $product = Product::create([
                                'name'          => $drug->display_name ?? $drug->name,
                                'code'          => $code,
                                'product_type'  => ProductType::DRUG->value,
                                'unit'          => $drug->unit ?? 'unit',
                                'description'   => $drug->description,
                                'reorder_level' => $drug->reorder_level,
                                'default_cost'  => null,
                                'base_price'    => $drug->price,
                                'is_billable'   => true,
                                'is_active'     => (bool) $drug->is_active,
                            ]);
                        }

                        $drug->updateQuietly(['product_id' => $product->id]);

                        if ($pharmacyDept) {
                            DB::table('product_department')->updateOrInsert(
                                ['product_id' => $product->id, 'department_id' => $pharmacyDept->id],
                                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                            );
                        }

                        $linked++;
                    });
                } catch (\Throwable $e) {
                    $skipped++;
                    $this->error(sprintf('  drug #%d (%s): %s', $drug->id, $drug->name, $e->getMessage()));
                }
            }
        });

        $this->info(sprintf('Done. Linked: %d. Skipped/errors: %d.', $linked, $skipped));

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Build a unique-ish product code from the drug. Falls back to DRUG-<id> if
     * the slugged name would collide.
     */
    private function buildProductCode(Drug $drug): string
    {
        $base = 'DRUG-' . strtoupper(Str::slug($drug->name ?? '', '_'));
        $candidate = $drug->strength
            ? $base . '-' . strtoupper(Str::slug($drug->strength, '_'))
            : $base;

        // Trim to 60 chars (products.code is varchar(60)).
        if (strlen($candidate) > 56) {
            $candidate = substr($candidate, 0, 56);
        }

        $exists = Product::query()->where('code', $candidate)->exists();
        if (! $exists) {
            return $candidate;
        }

        return 'DRUG-' . $drug->id;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\DepartmentType;
use App\Enums\InvestigationItemCategory;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Department;
use App\Models\InvestigationItem;
use App\Models\InvestigationItemStock;
use App\Models\Product;
use App\Models\StockLocation;
use App\Services\ProductStockMovementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * inventory:link-investigation-items-to-products
 *
 * For each InvestigationItem that does not yet have an associated Product,
 * this command:
 *   1. Creates a Product (product_type derived from InvestigationItemCategory)
 *      carrying the item's identity (name, code, unit, reorder_level).
 *   2. Links investigation_items.product_id to the new Product.
 *   3. Attaches the product to all departments of type INVESTIGATION /
 *      RADIOLOGY (so lab/imaging dispensing UIs can see it).
 *   4. For each existing InvestigationItemStock row with qty > 0, emits a
 *      one-time OPENING_STOCK movement against the resolved "Lab Store"
 *      stock_location so the unified product ledger reflects current on-hand
 *      stock without double-counting future operations.
 *
 * Idempotent: items that already have product_id are skipped; opening seeding
 * skips investigation_item_stock rows that have already been seeded (tracked
 * by a notes marker).
 */
class LinkInvestigationItemsToProductsCommand extends Command
{
    protected $signature = 'inventory:link-investigation-items-to-products
                            {--dry-run : Show what would happen without writing}
                            {--item= : Only link a single investigation item by id}
                            {--no-opening : Skip OPENING_STOCK ledger seeding from investigation_item_stock}';

    protected $description = 'Backfill a Product per InvestigationItem and seed opening stock into the unified ledger.';

    public function __construct(private ProductStockMovementService $movements)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dry  = (bool) $this->option('dry-run');
        $only = $this->option('item') ? (int) $this->option('item') : null;

        // Resolve investigation-style departments (lab, radiology) for the
        // product_department pivot. There may be more than one.
        $deptIds = Department::query()
            ->whereIn('type', [
                DepartmentType::INVESTIGATION->value,
                DepartmentType::RADIOLOGY->value,
            ])
            ->pluck('id')
            ->all();

        if (empty($deptIds)) {
            $this->warn('No INVESTIGATION/RADIOLOGY departments found — products will be created but not pivot-linked.');
        }

        $labStore = StockLocation::query()
            ->where('type', 'lab')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        if (! $labStore) {
            $this->warn('No Lab Store (stock_locations.type=lab) found — opening stock will not be seeded.');
        }

        $query = InvestigationItem::query()->whereNull('product_id');
        if ($only) {
            $query->whereKey($only);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('All investigation items are already linked to products.');
            // Still allow opening-stock seeding for previously linked items.
            if (! $this->option('no-opening') && $labStore) {
                $this->seedOpeningStock($dry, $labStore);
            }
            return self::SUCCESS;
        }

        $this->info(sprintf('Linking %d investigation item(s) to products%s...', $total, $dry ? ' (dry-run)' : ''));

        $linked  = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(100, function ($items) use (&$linked, &$skipped, $dry, $deptIds) {
            foreach ($items as $item) {
                /** @var InvestigationItem $item */
                try {
                    $code = $this->buildProductCode($item);
                    $type = $this->mapCategoryToProductType($item->category);

                    if ($dry) {
                        $this->line(sprintf(
                            '  [dry-run] would create product code=%s type=%s for item #%d (%s)',
                            $code, $type->value, $item->id, $item->name,
                        ));
                        $linked++;
                        continue;
                    }

                    DB::transaction(function () use ($item, $code, $type, $deptIds, &$linked) {
                        $product = Product::query()->where('code', $code)->first();

                        if (! $product) {
                            $product = Product::create([
                                'name'          => $item->name,
                                'code'          => $code,
                                'product_type'  => $type->value,
                                'unit'          => $item->unit ?? 'unit',
                                'description'   => $item->description,
                                'reorder_level' => $item->reorder_level,
                                'default_cost'  => null,
                                'base_price'    => 0,            // investigation items are consumed, not billed directly
                                'is_billable'   => false,
                                'is_active'     => (bool) $item->is_active,
                            ]);
                        }

                        $item->updateQuietly(['product_id' => $product->id]);

                        foreach ($deptIds as $deptId) {
                            DB::table('product_department')->updateOrInsert(
                                ['product_id' => $product->id, 'department_id' => $deptId],
                                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                            );
                        }

                        $linked++;
                    });
                } catch (\Throwable $e) {
                    $skipped++;
                    $this->error(sprintf('  item #%d (%s): %s', $item->id, $item->name, $e->getMessage()));
                }
            }
        });

        $this->info(sprintf('Catalog link done. Linked: %d. Skipped/errors: %d.', $linked, $skipped));

        if (! $this->option('no-opening') && $labStore) {
            $this->seedOpeningStock($dry, $labStore);
        }

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Emit OPENING_STOCK movements for every investigation_item_stock row that
     * carries quantity > 0 and whose investigation_item has been linked to a
     * product. Marker note prevents double-seeding on re-run.
     */
    private function seedOpeningStock(bool $dry, StockLocation $labStore): void
    {
        $marker = 'opening-from-investigation_item_stock#';

        $rows = InvestigationItemStock::query()
            ->with('item')
            ->where('quantity', '>', 0)
            ->whereHas('item', fn ($q) => $q->whereNotNull('product_id'))
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No investigation_item_stock rows to seed.');
            return;
        }

        $this->info(sprintf('Seeding opening stock for %d batch row(s)%s...', $rows->count(), $dry ? ' (dry-run)' : ''));

        $seeded = 0;
        foreach ($rows as $row) {
            $productId = $row->item?->product_id;
            if (! $productId) continue;

            // Already seeded?
            $exists = DB::table('product_stock_movements')
                ->where('product_id', $productId)
                ->where('source_type', InvestigationItemStock::class)
                ->where('source_id', $row->id)
                ->exists();
            if ($exists) continue;

            if ($dry) {
                $this->line(sprintf(
                    '  [dry-run] OPENING_STOCK +%s of product #%d (item %s) at %s',
                    (string) $row->quantity, $productId, $row->item->name ?? '?', $labStore->name,
                ));
                $seeded++;
                continue;
            }

            try {
                $this->movements->createMovement([
                    'product_id'        => $productId,
                    'stock_location_id' => $labStore->id,
                    'movement_type'     => StockMovementType::OPENING_STOCK,
                    'quantity'          => (float) $row->quantity,
                    'unit_cost'         => $row->unit_cost,
                    'batch_no'          => $row->batch_number,
                    'expiry_date'       => $row->expiry_date,
                    'source_type'       => InvestigationItemStock::class,
                    'source_id'         => $row->id,
                    'notes'             => $marker . $row->id,
                ]);
                $seeded++;
            } catch (\Throwable $e) {
                $this->error(sprintf(
                    '  seed failed for stock #%d (item %s): %s',
                    $row->id, $row->item->name ?? '?', $e->getMessage(),
                ));
            }
        }

        $this->info(sprintf('Opening stock seeded: %d row(s).', $seeded));
    }

    private function buildProductCode(InvestigationItem $item): string
    {
        if ($item->code) {
            $candidate = 'INV-' . strtoupper(Str::slug($item->code, '_'));
        } else {
            $candidate = 'INV-' . strtoupper(Str::slug($item->name ?? '', '_'));
        }

        if (strlen($candidate) > 56) {
            $candidate = substr($candidate, 0, 56);
        }

        $exists = Product::query()->where('code', $candidate)->exists();
        if (! $exists) {
            return $candidate;
        }

        return 'INV-' . $item->id;
    }

    private function mapCategoryToProductType(InvestigationItemCategory|string|null $category): ProductType
    {
        if ($category instanceof InvestigationItemCategory) {
            $category = $category->value;
        }
        return match ($category) {
            InvestigationItemCategory::REAGENT->value    => ProductType::REAGENT,
            InvestigationItemCategory::TEST_KIT->value   => ProductType::REAGENT,
            InvestigationItemCategory::CONSUMABLE->value => ProductType::CONSUMABLE,
            InvestigationItemCategory::RADIOLOGY->value  => ProductType::MEDICAL_SUPPLY,
            InvestigationItemCategory::IMAGING->value    => ProductType::MEDICAL_SUPPLY,
            default                                      => ProductType::GENERAL_ITEM,
        };
    }
}

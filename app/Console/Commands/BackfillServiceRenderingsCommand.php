<?php

namespace App\Console\Commands;

use App\Models\InvoiceItem;
use App\Services\ServiceRenderingService;
use Illuminate\Console\Command;

class BackfillServiceRenderingsCommand extends Command
{
    protected $signature = 'service-renderings:backfill {--dry-run : Show what would be created without saving}';

    protected $description = 'Create service renderings for existing invoice items that do not have one yet.';

    public function handle(ServiceRenderingService $service): int
    {
        $dryRun = $this->option('dry-run');

        $items = InvoiceItem::query()
            ->whereNotNull('service_catalog_id')
            ->whereDoesntHave('serviceRendering')
            ->with([
                'invoice',
                'visit.emergencyCase',
                'visit.admission',
                'visit.activeConsultationRoute',
                'patient',
                'department',
                'serviceCatalog.department',
            ])
            ->cursor();

        $created = 0;
        $skipped = 0;

        foreach ($items as $item) {
            if ($dryRun) {
                $this->line("Would process item #{$item->id}: {$item->description}");
                $created++;
                continue;
            }

            $rendering = $service->createForInvoiceItem($item);

            if ($rendering) {
                $created++;
            } else {
                $skipped++;
            }
        }

        if ($dryRun) {
            $this->info("Dry run: {$created} item(s) would be processed.");
        } else {
            $this->info("Done. Created: {$created}, Skipped (not trackable): {$skipped}.");
        }

        return self::SUCCESS;
    }
}

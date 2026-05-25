<?php

namespace App\Console\Commands;

use App\Models\InvoiceItem;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteLog;
use App\Models\VisitConsultationRouteService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MigrateConsultationRoutesToDepartmentsCommand extends Command
{
    protected $signature = 'consultation:migrate-service-routes-to-departments';

    protected $description = 'Merge service-based consultation routes into one department route per visit and link services under that route.';

    public function handle(): int
    {
        $merged = 0;
        $linked = 0;

        DB::transaction(function () use (&$merged, &$linked) {
            $groups = VisitConsultationRoute::query()
                ->with(['routeServices', 'logs'])
                ->orderBy('visit_id')
                ->orderBy('department_id')
                ->get()
                ->groupBy(fn (VisitConsultationRoute $route) => "{$route->visit_id}:{$route->department_id}");

            foreach ($groups as $group) {
                $group = $group->values();
                $survivor = $this->pickSurvivor($group);

                foreach ($group as $route) {
                    $linked += $this->copyRouteServices($route, $survivor);

                    if ($route->is($survivor)) {
                        continue;
                    }

                    $this->mergeRouteInto($route, $survivor);
                    $route->delete();
                    $merged++;
                }

                $this->refreshLegacyPrimaryService($survivor);
            }
        });

        $this->info("Consultation routes migrated. Merged {$merged} duplicate route(s), linked {$linked} service(s).");

        return self::SUCCESS;
    }

    private function pickSurvivor(Collection $routes): VisitConsultationRoute
    {
        return $routes
            ->sortByDesc(fn (VisitConsultationRoute $route) => [
                $this->statusRank($route->status),
                optional($route->updated_at)->timestamp ?? 0,
                $route->id,
            ])
            ->first();
    }

    private function statusRank(?string $status): int
    {
        return match ($status) {
            VisitConsultationRoute::STATUS_ACTIVE => 50,
            VisitConsultationRoute::STATUS_PAUSED => 40,
            VisitConsultationRoute::STATUS_PENDING => 30,
            VisitConsultationRoute::STATUS_COMPLETED => 20,
            VisitConsultationRoute::STATUS_CANCELLED => 10,
            default => 0,
        };
    }

    private function copyRouteServices(VisitConsultationRoute $source, VisitConsultationRoute $target): int
    {
        $count = 0;

        $serviceIds = $source->routeServices
            ->pluck('service_id')
            ->when($source->service_id, fn ($ids) => $ids->push($source->service_id))
            ->unique()
            ->values();

        foreach ($serviceIds as $serviceId) {
            $invoiceItem = InvoiceItem::where('visit_id', $target->visit_id)
                ->where('service_catalog_id', $serviceId)
                ->orderBy('id')
                ->first();

            VisitConsultationRouteService::updateOrCreate(
                [
                    'visit_consultation_route_id' => $target->id,
                    'service_id' => $serviceId,
                ],
                [
                    'visit_id' => $target->visit_id,
                    'invoice_item_id' => $invoiceItem?->id,
                ]
            );

            $count++;
        }

        return $count;
    }

    private function mergeRouteInto(VisitConsultationRoute $source, VisitConsultationRoute $target): void
    {
        $updates = [];

        if (! $target->doctor_id && $source->doctor_id) {
            $updates['doctor_id'] = $source->doctor_id;
        } elseif ($target->doctor_id && $source->doctor_id && (int) $target->doctor_id !== (int) $source->doctor_id) {
            $updates['notes'] = trim(($target->notes ? $target->notes.PHP_EOL : '')
                ."Merged service route #{$source->id}; original doctor_id={$source->doctor_id}.");
        }

        foreach (['routed_by', 'started_by', 'completed_by'] as $column) {
            if (! $target->{$column} && $source->{$column}) {
                $updates[$column] = $source->{$column};
            }
        }

        foreach (['started_at', 'activated_at', 'paused_at', 'completed_at'] as $column) {
            if (! $target->{$column} && $source->{$column}) {
                $updates[$column] = $source->{$column};
            }
        }

        if ($updates) {
            $target->forceFill($updates)->save();
        }

        DB::table('medical_records')
            ->where('consultation_route_id', $source->id)
            ->update([
                'consultation_route_id' => $target->id,
                'department_id' => $target->department_id,
            ]);

        VisitConsultationRouteLog::where('visit_consultation_route_id', $source->id)
            ->update(['visit_consultation_route_id' => $target->id]);

        VisitConsultationRouteLog::create([
            'visit_consultation_route_id' => $target->id,
            'visit_id' => $target->visit_id,
            'from_status' => $source->status,
            'to_status' => $target->status,
            'action' => 'merged_service_route',
            'notes' => "Merged service-based consultation route #{$source->id} into department route #{$target->id}.",
            'performed_by' => null,
        ]);
    }

    private function refreshLegacyPrimaryService(VisitConsultationRoute $route): void
    {
        $firstServiceId = $route->routeServices()
            ->orderBy('id')
            ->value('service_id');

        if ($firstServiceId && (int) $route->service_id !== (int) $firstServiceId) {
            $route->forceFill(['service_id' => $firstServiceId])->save();
        }
    }
}

<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\SampleStatus;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\Sample;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SampleService
{
    public function __construct(
        protected ActivityLogService $activityLog,
        protected VisitPathwayService $pathway,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Create one sample per distinct specimen type among the request's items and
     * link each item to its matching sample. Items whose test declares no
     * specimen type fall into the configured default specimen. Idempotent: items
     * already linked to a sample are left untouched.
     */
    public function generateForRequest(LabRequest $request): Collection
    {
        return DB::transaction(function () use ($request) {
            $request->loadMissing(['items.labTest', 'samples']);

            $default = (string) config('specimens.default', 'other');
            $created = collect();

            // Existing samples on the request, keyed by specimen type, so a second
            // "generate" only fills gaps rather than duplicating.
            $byType = $request->samples->keyBy('specimen_type');

            $unassigned = $request->items->whereNull('sample_id');

            foreach ($unassigned->groupBy(fn (LabRequestItem $item) => $this->specimenTypeFor($item, $default)) as $type => $items) {
                $sample = $byType->get($type);

                if (! $sample) {
                    // Barcode defaults to the sample number for analyzer matching.
                    $number = Sample::generateSampleNumber();
                    $sample = $request->samples()->create([
                        'sample_number' => $number,
                        'barcode'       => $number,
                        'specimen_type' => $type,
                        'container'     => config("specimens.types.{$type}.container"),
                        'status'        => SampleStatus::PENDING->value,
                    ]);
                    $byType->put($type, $sample);
                    $created->push($sample);
                }

                LabRequestItem::whereIn('id', $items->pluck('id'))->update(['sample_id' => $sample->id]);
            }

            if ($created->isNotEmpty()) {
                $this->log($created->first(), 'SAMPLE_GENERATED', __('samples.log.generated', [
                    'count'  => $created->count(),
                    'number' => $request->request_number,
                ]));
            }

            return $request->samples()->with('items')->get();
        });
    }

    /** Resolve the specimen type for an item from its lab test, defaulting when unmapped. */
    protected function specimenTypeFor(LabRequestItem $item, string $default): string
    {
        $type = $item->labTest?->default_specimen_type;

        if ($type && array_key_exists($type, config('specimens.types', []))) {
            return $type;
        }

        return $default;
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle transitions
    |--------------------------------------------------------------------------
    */

    public function collect(Sample $sample, array $data = []): Sample
    {
        if ($sample->isTerminal()) {
            throw new \RuntimeException(__('samples.errors.terminal'));
        }

        $sample->fill(array_filter([
            'barcode'   => $data['barcode'] ?? null,
            'container' => $data['container'] ?? null,
            'notes'     => $data['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        $sample->status       = SampleStatus::COLLECTED;
        $sample->collected_by = Auth::id();
        $sample->collected_at = now();
        // Re-collection clears a previous rejection.
        $sample->rejected_by = null;
        $sample->rejected_at = null;
        $sample->rejection_reason = null;
        $sample->save();

        $this->pathwayEvent($sample, 'SAMPLE_COLLECTED', __('samples.pathway.collected'));
        $this->log($sample, 'SAMPLE_COLLECTED', __('samples.log.collected', ['number' => $sample->sample_number]));

        return $sample;
    }

    public function receive(Sample $sample): Sample
    {
        if ($sample->status_enum !== SampleStatus::COLLECTED) {
            throw new \RuntimeException(__('samples.errors.receive_requires_collected'));
        }

        $sample->status      = SampleStatus::RECEIVED;
        $sample->received_by = Auth::id();
        $sample->received_at = now();
        $sample->save();

        $this->pathwayEvent($sample, 'SAMPLE_RECEIVED', __('samples.pathway.received'));
        $this->log($sample, 'SAMPLE_RECEIVED', __('samples.log.received', ['number' => $sample->sample_number]));

        return $sample;
    }

    public function reject(Sample $sample, string $reason): Sample
    {
        if ($sample->isTerminal()) {
            throw new \RuntimeException(__('samples.errors.terminal'));
        }

        $sample->status           = SampleStatus::REJECTED;
        $sample->rejected_by      = Auth::id();
        $sample->rejected_at      = now();
        $sample->rejection_reason = $reason;
        $sample->save();

        $this->pathwayEvent($sample, 'SAMPLE_REJECTED', __('samples.pathway.rejected'));
        $this->log($sample, 'SAMPLE_REJECTED', __('samples.log.rejected', [
            'number' => $sample->sample_number,
            'reason' => $reason,
        ]));

        return $sample;
    }

    public function dispose(Sample $sample): Sample
    {
        if ($sample->status_enum === SampleStatus::DISPOSED) {
            throw new \RuntimeException(__('samples.errors.already_disposed'));
        }

        $sample->status      = SampleStatus::DISPOSED;
        $sample->disposed_by = Auth::id();
        $sample->disposed_at = now();
        $sample->save();

        $this->log($sample, 'SAMPLE_DISPOSED', __('samples.log.disposed', ['number' => $sample->sample_number]));

        return $sample;
    }

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    public function getSampleQueue(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Sample::with([
            'labRequest.patient',
            'labRequest.targetDepartment',
            'collectedBy',
            'receivedBy',
            'items',
        ])->latest();

        if (! empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (! empty($filters['specimen_type'])) {
            $query->specimenType($filters['specimen_type']);
        }

        if (! empty($filters['target_department_id'])) {
            $query->whereHas('labRequest', fn ($q) => $q->where('target_department_id', $filters['target_department_id']));
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getStats(): array
    {
        return [
            'pending'   => Sample::status(SampleStatus::PENDING->value)->count(),
            'collected' => Sample::status(SampleStatus::COLLECTED->value)->count(),
            'received'  => Sample::status(SampleStatus::RECEIVED->value)->whereDate('received_at', today())->count(),
            'rejected'  => Sample::status(SampleStatus::REJECTED->value)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    protected function log(Sample $sample, string $action, string $description): void
    {
        $this->activityLog->log(
            LogModule::INVESTIGATION,
            $action,
            $sample->toActivityContext() + ['metadata' => array_filter([
                'specimen_type' => $sample->specimen_type,
                'status'        => $sample->status_enum->value,
            ])],
            $sample,
            $description,
        );
    }

    protected function pathwayEvent(Sample $sample, string $eventType, string $title): void
    {
        $sample->loadMissing('labRequest.visit');
        $request = $sample->labRequest;

        if ($request?->visit) {
            $this->pathway->record($request->visit, $eventType, [
                'source'        => $sample,
                'department_id' => $request->target_department_id ?? $request->department_id,
                'title'         => $title,
                'description'   => $sample->sample_number . ' · ' . $sample->specimenLabel(),
            ]);
        }
    }
}

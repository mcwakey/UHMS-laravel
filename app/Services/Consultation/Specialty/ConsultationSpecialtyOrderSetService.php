<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyOrderSetApplication;
use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationTask;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ClinicalFrequencyOptionService;
use App\Services\Consultation\ConsultationTaskFrequencyExpansionService;
use App\Services\MedicalRecordEntryLogService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConsultationSpecialtyOrderSetService
{
    private const SAFE_APPLY_MODES = ['create_task', 'patch_specialty_entry'];

    public function __construct(
        private readonly ConsultationSpecialtyEntryService $entries,
        private readonly ConsultationTaskFrequencyExpansionService $tasks,
        private readonly ClinicalFrequencyOptionService $frequencies,
        private readonly MedicalRecordEntryLogService $entryLogs,
    ) {}

    public function getOrderSetsForProfile(ConsultationSpecialtyProfile $profile): EloquentCollection
    {
        if (! $profile->is_active) {
            return new EloquentCollection();
        }

        return ConsultationSpecialtyOrderSet::query()
            ->withCount(['activeItems as items_count'])
            ->forProfile($profile)
            ->active()
            ->ordered()
            ->get();
    }

    public function getWorkspaceOrderSets(ResolvedConsultationSpecialty|array $context): array
    {
        $profile = $context instanceof ResolvedConsultationSpecialty
            ? $context->profile
            : ConsultationSpecialtyProfile::query()->find(data_get($context, 'profile.id'));

        if (! $profile instanceof ConsultationSpecialtyProfile) {
            return [];
        }

        return $this->getOrderSetsForProfile($profile)
            ->map(fn (ConsultationSpecialtyOrderSet $orderSet) => [
                'id' => $orderSet->id,
                'code' => $orderSet->code,
                'name' => $orderSet->name,
                'description' => $orderSet->description,
                'category' => $orderSet->category,
                'icon' => $orderSet->icon,
                'color' => $orderSet->color,
                'items_count' => $orderSet->items_count ?? $orderSet->activeItems()->count(),
            ])
            ->values()
            ->all();
    }

    public function previewOrderSet($consultation, ConsultationSpecialtyOrderSet $orderSet, User $user, array $options = []): array
    {
        $orderSet->loadMissing('activeItems');
        $warnings = [];

        if (! $orderSet->is_active) {
            $warnings[] = __('consultation_specialties.order_sets.inactive_warning');
        }

        return [
            'order_set' => [
                'id' => $orderSet->id,
                'code' => $orderSet->code,
                'name' => $orderSet->name,
                'description' => $orderSet->description,
            ],
            'items' => $orderSet->activeItems
                ->map(fn (ConsultationSpecialtyOrderSetItem $item) => $this->previewItem($item))
                ->values()
                ->all(),
            'warnings' => $warnings,
        ];
    }

    public function applyOrderSet(
        $consultation,
        ConsultationSpecialtyOrderSet $orderSet,
        User $user,
        array $selectedItemIds = [],
        array $options = []
    ): ConsultationSpecialtyOrderSetApplication {
        $route = $this->route($consultation);
        $profile = $orderSet->profile;
        $preview = $this->previewOrderSet($route, $orderSet, $user, $options);
        $selected = collect($selectedItemIds)->map(fn ($id) => (int) $id)->filter()->values();

        return DB::transaction(function () use ($route, $orderSet, $profile, $user, $preview, $selected, $options) {
            $application = ConsultationSpecialtyOrderSetApplication::query()->create([
                'consultation_id' => $route->id,
                'consultation_specialty_order_set_id' => $orderSet->id,
                'consultation_specialty_profile_id' => $profile?->id,
                'applied_by' => $user->id,
                'status' => 'previewed',
                'preview_payload' => $preview,
                'warnings' => $preview['warnings'] ?? [],
                'metadata' => ['source' => 'consultation_workspace'],
            ]);

            $items = $orderSet->activeItems()->get();
            if ($selected->isNotEmpty()) {
                $items = $items->whereIn('id', $selected->all())->values();
            } else {
                $items = $items->filter(fn (ConsultationSpecialtyOrderSetItem $item) => in_array($item->apply_mode, self::SAFE_APPLY_MODES, true))->values();
            }

            $results = $items->map(fn (ConsultationSpecialtyOrderSetItem $item) => $this->applyItem($application, $route, $profile, $item, $user, $options));
            $statuses = $results->pluck('status');
            $status = $statuses->contains('failed')
                ? 'failed'
                : ($statuses->contains(fn ($value) => $value !== 'applied') ? 'partially_applied' : 'applied');

            if ($results->isEmpty()) {
                $status = 'previewed';
            }

            $application->update([
                'status' => $status,
                'applied_payload' => ['items' => $results->values()->all()],
                'warnings' => collect($preview['warnings'] ?? [])
                    ->merge($results->flatMap(fn ($result) => $result['warnings'] ?? []))
                    ->values()
                    ->all(),
            ]);

            $this->entryLogs->created($application, $user);

            return $application->fresh(['items']);
        });
    }

    private function previewItem(ConsultationSpecialtyOrderSetItem $item): array
    {
        $warnings = [];
        $canApply = in_array($item->apply_mode, self::SAFE_APPLY_MODES, true);

        if ($item->favoritable_type && $item->favoritable_id && ! $item->favoritable) {
            $canApply = false;
            $warnings[] = __('consultation_specialties.order_sets.linked_record_missing');
        }

        if (! $canApply && $item->apply_mode !== 'suggest') {
            $warnings[] = __('consultation_specialties.order_sets.manual_only_warning');
        }

        return [
            'id' => $item->id,
            'type' => $item->item_type,
            'label' => $item->label,
            'apply_mode' => $item->apply_mode,
            'can_apply' => $canApply,
            'will_create' => $item->apply_mode === 'create_task' ? 'consultation_task' : null,
            'will_update' => $item->apply_mode === 'patch_specialty_entry' ? 'consultation_specialty_entry' : null,
            'requires_manual_action' => ! $canApply,
            'warnings' => $warnings,
            'payload' => $item->payload ?? [],
        ];
    }

    private function applyItem(
        ConsultationSpecialtyOrderSetApplication $application,
        VisitConsultationRoute $route,
        ?ConsultationSpecialtyProfile $profile,
        ConsultationSpecialtyOrderSetItem $item,
        User $user,
        array $options
    ): array {
        try {
            $result = match ($item->apply_mode) {
                'create_task' => $this->applyTask($route, $item, $user),
                'patch_specialty_entry' => $this->applySpecialtyEntryPatch($route, $profile, $item, $user, $options),
                'suggest', 'insert_text' => ['status' => 'suggested', 'message' => __('consultation_specialties.order_sets.manual_action')],
                default => ['status' => 'unsupported', 'message' => __('consultation_specialties.order_sets.unsupported')],
            };
        } catch (\Throwable $e) {
            $result = ['status' => $item->is_required ? 'failed' : 'skipped', 'message' => $e->getMessage(), 'warnings' => [$e->getMessage()]];
        }

        $application->items()->create([
            'consultation_specialty_order_set_item_id' => $item->id,
            'item_type' => $item->item_type,
            'label' => $item->label,
            'apply_mode' => $item->apply_mode,
            'status' => $result['status'],
            'target_type' => $result['target_type'] ?? null,
            'target_id' => $result['target_id'] ?? null,
            'payload' => $item->payload ?? [],
            'message' => $result['message'] ?? null,
            'warnings' => $result['warnings'] ?? [],
        ]);

        return $result + ['item_id' => $item->id, 'label' => $item->label, 'apply_mode' => $item->apply_mode];
    }

    private function applyTask(VisitConsultationRoute $route, ConsultationSpecialtyOrderSetItem $item, User $user): array
    {
        $record = $route->medicalRecord;
        if (! $record) {
            return ['status' => 'unsupported', 'message' => __('consultation_specialties.order_sets.no_medical_record')];
        }

        $payload = $item->payload ?? [];
        $frequency = strtoupper((string) ($payload['frequency'] ?? 'OD'));
        if (! in_array($frequency, $this->frequencies->values(), true)) {
            $frequency = 'OD';
        }

        $title = (string) ($payload['title'] ?? $item->label);
        $existing = ConsultationTask::query()
            ->where('consultation_route_id', $route->id)
            ->where('title', $title)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        if ($existing) {
            return [
                'status' => 'skipped',
                'target_type' => ConsultationTask::class,
                'target_id' => $existing->id,
                'message' => __('consultation_specialties.order_sets.duplicate_task_skipped'),
            ];
        }

        $tasks = $this->tasks->createTasks($record, $route, [
            'title' => $title,
            'description' => $payload['description'] ?? __('consultation_specialties.order_sets.from_order_set'),
            'priority' => $payload['priority'] ?? 'medium',
            'frequency' => $frequency,
            'start_at' => $payload['start_at'] ?? null,
            'due_date' => $payload['due_date'] ?? null,
        ], $user);

        $task = $tasks->first();

        return [
            'status' => 'applied',
            'target_type' => ConsultationTask::class,
            'target_id' => $task?->id,
            'message' => trans_choice('messages.consultation_tasks.created_count', max(1, $tasks->count()), ['count' => max(1, $tasks->count())]),
        ];
    }

    private function applySpecialtyEntryPatch(
        VisitConsultationRoute $route,
        ?ConsultationSpecialtyProfile $profile,
        ConsultationSpecialtyOrderSetItem $item,
        User $user,
        array $options
    ): array {
        if (! $profile) {
            return ['status' => 'unsupported', 'message' => __('consultation_specialties.order_sets.profile_missing')];
        }

        $payload = $item->payload ?? [];
        $sectionKey = (string) ($payload['section_key'] ?? $item->target_section ?? '');
        $merge = (array) ($payload['merge'] ?? []);
        if ($sectionKey === '' || $merge === []) {
            return ['status' => 'unsupported', 'message' => __('consultation_specialties.order_sets.invalid_patch')];
        }

        $existing = $this->entries->getEntry($route, $profile, $sectionKey);
        $current = $existing?->entry ?? [];
        $merged = $this->mergeMissing($current, $merge, (bool) ($options['overwrite'] ?? false));

        if ($merged === $current) {
            return [
                'status' => 'skipped',
                'target_type' => $existing ? $existing::class : null,
                'target_id' => $existing?->id,
                'message' => __('consultation_specialties.order_sets.existing_values_preserved'),
            ];
        }

        $entry = $this->entries->upsertEntry($route, $profile, $sectionKey, $merged, $user);

        return [
            'status' => 'applied',
            'target_type' => $entry::class,
            'target_id' => $entry->id,
            'message' => __('consultation_specialties.messages.section_saved'),
        ];
    }

    private function mergeMissing(array $current, array $patch, bool $overwrite): array
    {
        foreach ($patch as $key => $value) {
            if ($overwrite || ! array_key_exists($key, $current) || $current[$key] === null || $current[$key] === '' || $current[$key] === []) {
                $current[$key] = $value;
            }
        }

        return $current;
    }

    private function route($consultation): VisitConsultationRoute
    {
        if ($consultation instanceof VisitConsultationRoute) {
            return $consultation->loadMissing('medicalRecord');
        }

        return VisitConsultationRoute::query()->with('medicalRecord')->findOrFail((int) $consultation);
    }
}

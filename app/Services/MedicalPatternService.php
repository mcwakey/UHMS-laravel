<?php

namespace App\Services;

use App\Models\MedicalPattern;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MedicalPatternService
{
    public function __construct(
        protected PatientComplaintService $patientComplaints,
    ) {}

    /**
     * List patterns with filters and pagination.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MedicalPattern::with(['doctor', 'items']);

        if (! empty($filters['search'])) {
            $query->where('name', 'LIKE', '%'.$filters['search'].'%');
        }

        if (isset($filters['scope'])) {
            if ($filters['scope'] === 'system') {
                $query->systemWide();
            } elseif ($filters['scope'] === 'mine') {
                $query->where('doctor_id', Auth::id());
            }
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderByDesc('usage_count')->paginate($perPage);
    }

    /**
     * Create a pattern with items.
     */
    public function create(array $data): MedicalPattern
    {
        $pattern = MedicalPattern::create([
            'name' => $data['name'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'is_active' => true,
        ]);

        if (! empty($data['items'])) {
            $sortOrder = 0;
            foreach ($data['items'] as $item) {
                $pattern->items()->create([
                    'type' => $item['type'],
                    'data' => $item['data'],
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return $pattern->load('items');
    }

    /**
     * Create a pattern from an existing medical record.
     */
    public function createFromRecord(MedicalRecord $record, string $name, ?int $doctorId = null): MedicalPattern
    {
        $record->load([
            'complaints.complaintCatalogue',
            'historiesOfPresentingComplaint',
            'physicalExaminations',
            'diagnoses',
            'investigations',
            'treatments',
            'prescriptions.items',
            'tasks',
        ]);

        $items = [];
        $sortOrder = 0;

        foreach ($record->complaints as $complaint) {
            $items[] = [
                'type' => 'complaint',
                'data' => [
                    'description' => $complaint->description,
                    'complaint_catalogue_id' => $complaint->complaint_catalogue_id,
                    'duration' => $complaint->duration,
                    'duration_unit' => $complaint->duration_unit,
                    'severity' => $complaint->severity,
                    'notes' => $complaint->notes,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->historiesOfPresentingComplaint as $hopc) {
            $items[] = [
                'type' => 'history_of_presenting_complaint',
                'data' => [
                    'content' => $hopc->content,
                    'onset' => $hopc->onset,
                    'duration' => $hopc->duration,
                    'location' => $hopc->location,
                    'character' => $hopc->character,
                    'severity' => $hopc->severity,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->physicalExaminations as $exam) {
            $items[] = [
                'type' => 'examination',
                'data' => [
                    'findings' => $exam->findings,
                    'general_examination' => $exam->general_examination,
                    'systemic_examination' => $exam->systemic_examination,
                    'specialty_examination' => $exam->specialty_examination,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->diagnoses as $diagnosis) {
            $items[] = [
                'type' => 'diagnosis',
                'data' => [
                    'icd_code' => $diagnosis->icd_code,
                    'description' => $diagnosis->description,
                    'type' => $diagnosis->type,
                    'notes' => $diagnosis->notes,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->treatments as $treatment) {
            $items[] = [
                'type' => 'treatment',
                'data' => [
                    'type' => $treatment->type,
                    'description' => $treatment->description,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->investigations as $investigation) {
            $items[] = [
                'type' => 'investigation',
                'data' => [
                    'investigation_type' => $investigation->investigation_type,
                    'description' => $investigation->description,
                    'urgency' => $investigation->urgency,
                    'notes' => $investigation->notes,
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->tasks as $task) {
            $items[] = [
                'type' => 'task',
                'data' => [
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'due_date' => optional($task->due_date)->toDateString(),
                ],
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($record->prescriptions as $prescription) {
            foreach ($prescription->items as $rxItem) {
                $items[] = [
                    'type' => 'prescription_item',
                    'data' => [
                        'drug_name' => $rxItem->drug_name,
                        'dosage' => $rxItem->dosage,
                        'frequency' => $rxItem->frequency,
                        'duration' => $rxItem->duration,
                        'quantity' => $rxItem->quantity,
                        'route' => $rxItem->route,
                        'instructions' => $rxItem->instructions,
                    ],
                    'sort_order' => $sortOrder++,
                ];
            }
        }

        $pattern = MedicalPattern::create([
            'name' => $name,
            'doctor_id' => $doctorId,
            'is_active' => true,
        ]);

        foreach ($items as $item) {
            $pattern->items()->create($item);
        }

        return $pattern->load('items');
    }

    /**
     * Update a pattern.
     */
    public function update(MedicalPattern $pattern, array $data): MedicalPattern
    {
        $pattern->update([
            'name' => $data['name'] ?? $pattern->name,
            'is_active' => $data['is_active'] ?? $pattern->is_active,
        ]);

        if (isset($data['items'])) {
            $pattern->items()->delete();
            $sortOrder = 0;
            foreach ($data['items'] as $item) {
                $pattern->items()->create([
                    'type' => $item['type'],
                    'data' => $item['data'],
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return $pattern->load('items');
    }

    /**
     * Suggest patterns based on complaint text (fuzzy match).
     */
    public function suggest(string $complaintText, int $doctorId, int $limit = 5): Collection
    {
        $keywords = array_filter(explode(' ', strtolower(trim($complaintText))));

        if (empty($keywords)) {
            return collect();
        }

        // Search patterns that have complaint items matching the text
        $query = MedicalPattern::active()
            ->forDoctor($doctorId)
            ->whereHas('items', function ($q) use ($keywords) {
                $q->where('type', 'complaint');
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) >= 3) {
                        $q->where('data', 'LIKE', '%'.$keyword.'%');
                    }
                }
            })
            ->with('items')
            ->orderByDesc('usage_count')
            ->limit($limit);

        // Also search by pattern name
        $nameQuery = MedicalPattern::active()
            ->forDoctor($doctorId)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) >= 3) {
                        $q->where('name', 'LIKE', '%'.$keyword.'%');
                    }
                }
            })
            ->with('items')
            ->orderByDesc('usage_count')
            ->limit($limit);

        return $query->get()
            ->merge($nameQuery->get())
            ->unique('id')
            ->take($limit);
    }

    /**
     * Apply a pattern to a medical record (creates the items).
     */
    public function applyPattern(MedicalPattern $pattern, MedicalRecord $record, ?array $sections = null, ?User $user = null): array
    {
        $user ??= Auth::user();
        $sections = collect($sections ?? $pattern->items->pluck('type')->unique()->all())
            ->map(fn ($section) => strtolower((string) $section))
            ->all();

        $applied = [
            'complaints' => [],
            'history_of_presenting_complaint' => [],
            'examinations' => [],
            'diagnoses' => [],
            'investigations' => [],
            'treatments' => [],
            'prescription_items' => [],
            'procedures' => [],
            'tasks' => [],
            'notes' => [],
        ];

        foreach ($pattern->items as $item) {
            if (! in_array(strtolower($item->type), $sections, true)) {
                continue;
            }

            $data = $item->data;
            $context = $this->entryContext($record, $user?->id, $pattern->id);

            switch ($item->type) {
                case 'complaint':
                    $applied['complaints'][] = $this->patientComplaints->createForRecord($record, [
                        'description' => $data['description'] ?? $data['name'] ?? '',
                        'complaint_catalogue_id' => $data['complaint_catalogue_id'] ?? null,
                        'duration' => $data['duration'] ?? null,
                        'duration_unit' => $data['duration_unit'] ?? null,
                        'severity' => $data['severity'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'source_pattern_id' => $pattern->id,
                    ], $user);
                    break;

                case 'history_of_presenting_complaint':
                case 'hopc':
                    $applied['history_of_presenting_complaint'][] = $record->historiesOfPresentingComplaint()->create([
                        ...$context,
                        'content' => $data['content'] ?? $data['description'] ?? '',
                        'onset' => $data['onset'] ?? null,
                        'duration' => $data['duration'] ?? null,
                        'location' => $data['location'] ?? null,
                        'character' => $data['character'] ?? null,
                        'severity' => $data['severity'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);
                    break;

                case 'examination':
                case 'physical_examination':
                    $applied['examinations'][] = $record->physicalExaminations()->create([
                        ...$context,
                        'findings' => $data['findings'] ?? $data['content'] ?? '',
                        'general_examination' => $data['general_examination'] ?? null,
                        'systemic_examination' => $data['systemic_examination'] ?? null,
                        'specialty_examination' => $data['specialty_examination'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);
                    break;

                case 'diagnosis':
                    $applied['diagnoses'][] = $record->diagnoses()->create([
                        ...$context,
                        'icd_code' => $data['icd_code'] ?? null,
                        'description' => $data['description'] ?? '',
                        'type' => $data['type'] ?? 'provisional',
                        'notes' => $data['notes'] ?? null,
                    ]);
                    break;

                case 'investigation':
                    $applied['investigations'][] = $record->investigations()->create([
                        ...$context,
                        'investigation_type' => $data['investigation_type'] ?? $data['description'] ?? 'Investigation suggestion',
                        'description' => $data['description'] ?? $data['investigation_type'] ?? 'Investigation suggested by pattern.',
                        'urgency' => $data['urgency'] ?? 'routine',
                        'status' => 'suggested',
                        'notes' => $data['notes'] ?? 'Pattern suggestion; confirm through investigation workflow before billing/requesting.',
                    ]);
                    break;

                case 'treatment':
                    $applied['treatments'][] = $record->treatments()->create([
                        ...$context,
                        'type' => $data['type'] ?? 'medication',
                        'description' => $data['description'] ?? '',
                    ]);
                    break;

                case 'prescription_item':
                case 'prescription':
                    $applied['prescription_items'][] = $data;
                    break;

                case 'procedure':
                    $applied['procedures'][] = $record->treatments()->create([
                        ...$context,
                        'type' => 'procedure',
                        'description' => $data['description'] ?? $data['indication'] ?? 'Procedure suggested by pattern.',
                    ]);
                    break;

                case 'task':
                case 'follow_up':
                    $applied['tasks'][] = $record->tasks()->create([
                        'medical_record_id' => $record->id,
                        'consultation_route_id' => $record->consultation_route_id,
                        'visit_id' => $record->visit_id,
                        'patient_id' => $record->patient_id,
                        'department_id' => $record->department_id,
                        'title' => $data['title'] ?? $data['description'] ?? 'Follow-up instruction',
                        'description' => $data['description'] ?? null,
                        'priority' => $data['priority'] ?? 'medium',
                        'status' => 'pending',
                        'due_date' => $data['due_date'] ?? null,
                        'created_by' => $user?->id,
                        'source_pattern_id' => $pattern->id,
                    ]);
                    break;

                case 'note':
                    $applied['notes'][] = $record->treatments()->create([
                        ...$context,
                        'type' => 'advice',
                        'description' => $data['content'] ?? $data['description'] ?? '',
                    ]);
                    break;
            }
        }

        $pattern->incrementUsage();

        // Audit the pattern application itself (under the current user). The
        // individual records created above are logged via their own services
        // where those route through MedicalRecordEntryLogService.
        app(\App\Services\ActivityLogService::class)->log(
            \App\Enums\LogModule::CONSULTATION,
            'PATTERN_APPLIED',
            [
                'patient_id' => $record->patient_id,
                'visit_id' => $record->visit_id,
                'medical_record_id' => $record->id,
                'consultation_route_id' => $record->consultation_route_id,
                'department_id' => $record->department_id,
                'metadata' => [
                    'pattern' => $pattern->name ?? ('#' . $pattern->id),
                    'applied_counts' => collect($applied)->map(fn ($a) => count($a))->filter()->all(),
                ],
            ],
            $pattern,
            'Medical pattern applied: ' . ($pattern->name ?? ('#' . $pattern->id)),
        );

        return $applied;
    }

    private function entryContext(MedicalRecord $record, ?int $userId, int $patternId): array
    {
        return [
            'consultation_route_id' => $record->consultation_route_id,
            'visit_id' => $record->visit_id,
            'patient_id' => $record->patient_id,
            'department_id' => $record->department_id,
            'doctor_id' => $userId,
            'created_by' => $userId,
            'source_pattern_id' => $patternId,
        ];
    }

    /**
     * Toggle pattern active status.
     */
    public function toggleActive(MedicalPattern $pattern): MedicalPattern
    {
        $pattern->update(['is_active' => ! $pattern->is_active]);

        return $pattern;
    }

    /**
     * Delete a pattern.
     */
    public function delete(MedicalPattern $pattern): void
    {
        $pattern->delete();
    }
}

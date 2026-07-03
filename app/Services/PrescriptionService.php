<?php

namespace App\Services;

use App\Enums\PrescriptionStatus;
use App\Events\PrescriptionCreated;
use App\Models\Drug;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PrescriptionService
{
    public function __construct(
        protected ConsultationContributorService $contributors,
        protected MedicalRecordEntryLogService $entryLogs,
    ) {}

    /**
     * List prescriptions with filters.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Prescription::with(['patient', 'doctor', 'visit', 'items']);

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('prescription_number', 'like', "%{$term}%")
                    ->orWhereHas('patient', function ($pq) use ($term) {
                        $pq->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('patient_number', 'like', "%{$term}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a prescription with items.
     */
    public function create(MedicalRecord $record, array $data): Prescription
    {
        if ($duplicate = $this->findNaturalDuplicate($record, $data)) {
            return $duplicate;
        }

        $prescription = $record->prescriptions()->create([
            'consultation_route_id' => $record->consultation_route_id,
            'visit_id' => $record->visit_id,
            'patient_id' => $record->patient_id,
            'department_id' => $record->department_id,
            'doctor_id' => Auth::id(),
            'created_by' => Auth::id(),
            'prescription_number' => Prescription::generatePrescriptionNumber(),
            'status' => PrescriptionStatus::PENDING->value,
            'notes' => $data['notes'] ?? null,
        ]);

        if (! empty($data['items'])) {
            foreach ($data['items'] as $item) {
                if (! empty($item['drug_id'])) {
                    $drug = Drug::find($item['drug_id']);
                    if ($drug) {
                        $item['drug_name'] = $drug->name;
                    }
                } elseif (! empty($item['drug_name'])) {
                    $drug = Drug::where('name', $item['drug_name'])->first();
                    if ($drug) {
                        $item['drug_id'] = $drug->id;
                    }
                }
                $prescription->items()->create($item);
            }
        }

        $prescription->load('items');
        if ($user = Auth::user()) {
            $this->contributors->recordContribution($record, $user, 'Prescription');
            $this->entryLogs->created($prescription, $user);
        }

        PrescriptionCreated::dispatch($prescription);
        app(MedicationOrderService::class)->createOrdersForPrescription($prescription);

        return $prescription;
    }

    private function findNaturalDuplicate(MedicalRecord $record, array $data): ?Prescription
    {
        $signature = $this->itemsSignature($data['items'] ?? []);
        if ($signature === []) {
            return null;
        }

        return $record->prescriptions()
            ->with('items')
            ->where('consultation_route_id', $record->consultation_route_id)
            ->where('created_by', Auth::id())
            ->where('created_at', '>=', now()->subMinute())
            ->latest('id')
            ->get()
            ->first(fn (Prescription $prescription) => $this->itemsSignature($prescription->items->map->toArray()->all()) === $signature);
    }

    private function itemsSignature(array $items): array
    {
        return collect($items)
            ->map(fn (array $item) => [
                'drug_id' => (int) ($item['drug_id'] ?? 0),
                'drug_name' => mb_strtolower(trim((string) ($item['drug_name'] ?? ''))),
                'dosage' => mb_strtolower(trim((string) ($item['dosage'] ?? ''))),
                'frequency' => mb_strtolower(trim((string) ($item['frequency'] ?? ''))),
                'duration' => mb_strtolower(trim((string) ($item['duration'] ?? ''))),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'route' => mb_strtolower(trim((string) ($item['route'] ?? ''))),
            ])
            ->sortBy(fn (array $item) => implode('|', $item))
            ->values()
            ->all();
    }

    /**
     * Add an item to an existing prescription.
     */
    public function addItem(Prescription $prescription, array $data): PrescriptionItem
    {
        return $prescription->items()->create($data);
    }

    /**
     * Update a prescription item.
     */
    public function updateItem(PrescriptionItem $item, array $data): PrescriptionItem
    {
        $item->update($data);

        return $item;
    }

    /**
     * Remove a prescription item.
     */
    public function removeItem(PrescriptionItem $item): void
    {
        $item->delete();
    }

    /**
     * Cancel a prescription.
     */
    public function cancel(Prescription $prescription): Prescription
    {
        $prescription->update(['status' => PrescriptionStatus::CANCELLED->value]);

        return $prescription;
    }
}

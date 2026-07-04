<?php

namespace App\Services\Consultation;

use App\Models\Complaint;
use Illuminate\Support\Arr;

class HopcComplaintHydrationService
{
    public function hydrate(array $data): array
    {
        $complaintId = Arr::get($data, 'complaint_id');
        if (! $complaintId) {
            return $data;
        }

        $complaint = Complaint::query()->find($complaintId);
        if (! $complaint) {
            return $data;
        }

        $data['content'] = $this->fill($data['content'] ?? null, $complaint->description);
        $data['duration'] = $this->fill($data['duration'] ?? null, $this->durationText($complaint));
        $data['severity'] = $this->fill($data['severity'] ?? null, $complaint->severity);

        return $data;
    }

    private function fill(?string $current, ?string $candidate): ?string
    {
        $current = trim((string) $current);
        $candidate = trim((string) $candidate);

        return $current !== '' ? $current : ($candidate !== '' ? $candidate : null);
    }

    private function durationText(Complaint $complaint): ?string
    {
        $duration = trim((string) $complaint->duration);
        if ($duration === '') {
            return null;
        }

        $unit = trim((string) $complaint->duration_unit);

        return trim($duration.' '.$unit) ?: null;
    }
}

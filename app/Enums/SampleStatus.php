<?php

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

/**
 * Lifecycle of a laboratory specimen (sample).
 *
 *   pending   → generated for a request, not yet drawn/collected
 *   collected → physically drawn from the patient
 *   received  → accessioned/received in the lab (ready for processing)
 *   rejected  → unsuitable (haemolysed, insufficient, mislabelled, …)
 *   disposed  → discarded after processing / retention period
 */
enum SampleStatus: string
{
    case PENDING   = 'pending';
    case COLLECTED = 'collected';
    case RECEIVED  = 'received';
    case REJECTED  = 'rejected';
    case DISPOSED  = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Awaiting Collection',
            self::COLLECTED => 'Collected',
            self::RECEIVED  => 'Received',
            self::REJECTED  => 'Rejected',
            self::DISPOSED  => 'Disposed',
        };
    }

    public function translatedLabel(): string
    {
        $key = 'samples.status.' . $this->value;

        return Lang::has($key) ? __($key) : $this->label();
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING   => 'warning',
            self::COLLECTED => 'info',
            self::RECEIVED  => 'success',
            self::REJECTED  => 'danger',
            self::DISPOSED  => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING   => 'ti-clock',
            self::COLLECTED => 'ti-droplet',
            self::RECEIVED  => 'ti-check',
            self::REJECTED  => 'ti-x',
            self::DISPOSED  => 'ti-trash',
        };
    }

    /** Whether the specimen has been received and processing/results may proceed. */
    public function isReceived(): bool
    {
        return $this === self::RECEIVED;
    }

    /** Whether the specimen is in a terminal state (no further transitions). */
    public function isTerminal(): bool
    {
        return in_array($this, [self::REJECTED, self::DISPOSED], true);
    }

    /** Statuses selectable as filters on the sample queue. */
    public static function selectableCases(): array
    {
        return [self::PENDING, self::COLLECTED, self::RECEIVED, self::REJECTED, self::DISPOSED];
    }
}

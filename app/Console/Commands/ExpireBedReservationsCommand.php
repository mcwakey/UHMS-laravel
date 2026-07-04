<?php

namespace App\Console\Commands;

use App\Models\BedReservation;
use App\Services\Admissions\BedWorkflowService;
use Illuminate\Console\Command;

class ExpireBedReservationsCommand extends Command
{
    protected $signature = 'admissions:expire-bed-reservations';

    protected $description = 'Expire overdue active bed reservations and safely release reserved beds.';

    public function handle(BedWorkflowService $beds): int
    {
        $checked = 0;
        $expired = 0;
        $skipped = 0;
        $errors = 0;

        BedReservation::query()
            ->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['bed.currentAdmission', 'admissionRequest'])
            ->orderBy('expires_at')
            ->chunkById(100, function ($reservations) use ($beds, &$checked, &$expired, &$skipped, &$errors) {
                foreach ($reservations as $reservation) {
                    $checked++;

                    try {
                        $result = $beds->expireReservation($reservation);
                        $result ? $expired++ : $skipped++;
                    } catch (\Throwable $exception) {
                        $errors++;
                        $this->error("Reservation {$reservation->id}: {$exception->getMessage()}");
                    }
                }
            });

        $this->info("Bed reservations checked={$checked} expired={$expired} skipped={$skipped} errors={$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}

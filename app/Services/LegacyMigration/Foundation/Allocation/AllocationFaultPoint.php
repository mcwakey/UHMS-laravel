<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

enum AllocationFaultPoint: string
{
    case BeforeReservationPersistence = 'before_reservation_persistence';
    case AfterReservationInsertBeforeSequenceCas = 'after_reservation_insert_before_sequence_cas';
    case AfterSequenceCasBeforeCommit = 'after_sequence_cas_before_commit';
}

<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

enum CollisionStatus: string
{
    case Available = 'available';
    case Collision = 'collision';
    case Unknown = 'unknown';
}

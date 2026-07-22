<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use App\Services\LegacyMigration\Evidence\TargetSchemaInspectionService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class TargetSchemaInspectionServiceTest extends TestCase
{
    #[Test]
    public function target_output_path_rejects_a_sibling_with_the_same_prefix(): void
    {
        $service = new TargetSchemaInspectionService(
            $this->createMock(DatabaseManager::class),
            new Filesystem,
        );
        $method = new ReflectionMethod($service, 'safeOutputPath');

        $this->expectException(RuntimeException::class);
        $method->invoke($service, 'docs/legacy-migration-other');
    }
}

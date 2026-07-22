<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use App\Services\LegacyMigration\Evidence\TargetSchemaInspectionService;
use App\Services\LegacyMigration\Evidence\UnexpectedTargetEnumRedactor;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class TargetSchemaInspectionServiceTest extends TestCase
{
    #[Test]
    public function unexpected_enum_values_are_counted_without_value_or_stable_unkeyed_digest(): void
    {
        $sensitive = 'SYNTHETIC-ONLY-P3B::UNEXPECTED';
        $result = (new UnexpectedTargetEnumRedactor)->redact([
            (object) ['category_value' => 'allowed', 'aggregate_count' => 2],
            (object) ['category_value' => $sensitive, 'aggregate_count' => 3],
        ], ['allowed']);
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);

        self::assertSame(3, $result['unexpected_value_count']);
        self::assertSame(1, $result['unexpected_distinct_value_count']);
        self::assertStringNotContainsString($sensitive, $encoded);
        self::assertStringNotContainsString(hash('sha256', $sensitive), $encoded);
        self::assertSame('count_only_no_digest', $result['unexpected_value_reporting']);
    }

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

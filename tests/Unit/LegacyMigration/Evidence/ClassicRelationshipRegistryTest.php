<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use App\Services\LegacyMigration\Evidence\ClassicEvidenceCaptureService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class ClassicRelationshipRegistryTest extends TestCase
{
    #[Test]
    public function registry_has_77_unique_evidence_labelled_relationships(): void
    {
        $service = new ClassicEvidenceCaptureService(
            $this->createMock(DatabaseManager::class),
            new Filesystem,
        );
        $method = new ReflectionMethod($service, 'relationshipDefinitions');
        $relationships = $method->invoke($service);
        $ids = array_column($relationships, 'id');

        $this->assertCount(77, $relationships);
        $this->assertCount(77, array_unique($ids));
        $this->assertContains('serv_results.claim_scan_lab_alternative', $ids);
        $this->assertContains('insurance.provider_name_candidate', $ids);
        $this->assertSame([], array_values(array_filter(
            $relationships,
            fn (array $relationship): bool => $relationship['relationship_status'] !== 'inferred',
        )));
    }

    #[Test]
    public function capture_refuses_every_connection_and_schema_except_legacy_uuhms(): void
    {
        $databases = $this->createMock(DatabaseManager::class);
        $databases->expects($this->never())->method('connection');
        $service = new ClassicEvidenceCaptureService($databases, new Filesystem);

        $this->expectException(RuntimeException::class);
        $service->capture('mysql', 'uhms_clean', 'docs/legacy-migration/evidence');
    }

    #[Test]
    public function evidence_output_path_cannot_escape_its_documentation_directory(): void
    {
        $service = new ClassicEvidenceCaptureService(
            $this->createMock(DatabaseManager::class),
            new Filesystem,
        );
        $method = new ReflectionMethod($service, 'safeOutputPath');

        $this->expectException(RuntimeException::class);
        $method->invoke($service, 'docs/legacy-migration/evidence/../../outside');
    }

    #[Test]
    public function evidence_output_path_rejects_a_sibling_with_the_same_prefix(): void
    {
        $service = new ClassicEvidenceCaptureService(
            $this->createMock(DatabaseManager::class),
            new Filesystem,
        );
        $method = new ReflectionMethod($service, 'safeOutputPath');

        $this->expectException(RuntimeException::class);
        $method->invoke($service, 'docs/legacy-migration/evidence-other');
    }
}

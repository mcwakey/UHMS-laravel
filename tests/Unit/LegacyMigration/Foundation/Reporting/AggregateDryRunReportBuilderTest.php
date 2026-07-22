<?php

namespace Tests\Unit\LegacyMigration\Foundation\Reporting;

use App\Services\LegacyMigration\Foundation\Reporting\AggregateDryRunReportBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AggregateDryRunReportBuilderTest extends TestCase
{
    #[Test]
    public function it_emits_only_nonbinding_zero_write_aggregate_reports(): void
    {
        $report = (new AggregateDryRunReportBuilder)->build(
            'run_01J000000000',
            'snapshot_01J000000000',
            ['eligible_count' => 12, 'exceptions' => ['duplicate_count' => 2]],
        );

        self::assertFalse($report['commit_authorized']);
        self::assertSame(0, $report['business_domain_write_count']);
        self::assertSame(0, $report['source_write_count']);
    }

    #[Test]
    public function it_rejects_record_level_fields_and_free_text(): void
    {
        $builder = new AggregateDryRunReportBuilder;

        $this->expectException(InvalidArgumentException::class);
        $builder->build('run_01J000000000', 'snapshot_01J000000000', [
            'patient_name' => 'Synthetic Person',
        ]);
    }

    #[Test]
    public function it_rejects_identifier_like_strings_hidden_under_neutral_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AggregateDryRunReportBuilder)->build(
            'run_01J000000000',
            'snapshot_01J000000000',
            ['value' => 'SYNTHETIC-ONLY-P3-IDENTIFIER'],
        );
    }
}

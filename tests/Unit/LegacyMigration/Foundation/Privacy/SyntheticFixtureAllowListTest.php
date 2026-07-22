<?php

namespace Tests\Unit\LegacyMigration\Foundation\Privacy;

use App\Services\LegacyMigration\Foundation\Privacy\SyntheticFixtureAllowList;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SyntheticFixtureAllowListTest extends TestCase
{
    #[Test]
    public function fixture_allowlist_is_path_namespace_and_marker_bound(): void
    {
        $document = [
            'fixture_namespace' => SyntheticFixtureAllowList::NAMESPACE,
            'fixture_literal_contract' => ['copied_or_perturbed_source' => false],
            'privacy' => ['contains_raw_phi' => false],
            'records' => [[
                'fixture_namespace' => SyntheticFixtureAllowList::NAMESPACE,
                'synthetic_only_assertion' => 'obviously_fictitious_independently_invented_never_copied_or_perturbed_from_classic_or_target',
            ]],
        ];
        $allowlist = new SyntheticFixtureAllowList;
        $json = json_encode($document, JSON_THROW_ON_ERROR);

        $this->assertTrue($allowlist->isValid(SyntheticFixtureAllowList::PATH, $json));
        $this->assertFalse($allowlist->isValid('exports/scenarios.json', $json));

        $document['records'][0]['fixture_namespace'] = 'NOT-APPROVED';
        $this->assertFalse($allowlist->isValid(
            SyntheticFixtureAllowList::PATH,
            json_encode($document, JSON_THROW_ON_ERROR),
        ));
    }
}

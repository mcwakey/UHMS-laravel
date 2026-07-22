<?php

namespace Tests\Support\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Validation\AuthoritativePolicyBundleLoader;
use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;

final class Phase2FPolicyFixture
{
    private static ?VerifiedPolicyBundle $bundle = null;

    public static function load(): VerifiedPolicyBundle
    {
        return self::$bundle ??= (new AuthoritativePolicyBundleLoader)->load(self::root(), self::expected());
    }

    /** @return array<string, array{sha256:string,specification_version?:string,contract_ids?:list<string>,approval_reference:string}> */
    public static function expected(): array
    {
        $expected = [];
        foreach (AuthoritativePolicyBundleLoader::REQUIRED_ARTIFACTS as $path) {
            $bytes = file_get_contents(self::root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));
            if (! is_string($bytes)) {
                throw new \RuntimeException('Authoritative fixture artifact cannot be read.');
            }
            $entry = [
                'sha256' => hash('sha256', $bytes),
                'approval_reference' => str_ends_with($path, '.json') ? 'Phase 2F independently approved package' : 'Project-owner migration decision authority',
            ];
            if (str_ends_with($path, '.json')) {
                $document = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
                $entry['specification_version'] = $document['specification_version'];
                $entry['contract_ids'] = array_map(static fn (array $record): string => $record['id'], $document['records']);
                sort($entry['contract_ids'], SORT_STRING);
            }
            $expected[$path] = $entry;
        }

        return $expected;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }
}

<?php

namespace Tests\Feature\Localization;

use Tests\TestCase;

/**
 * Phase 16 localisation gate: EN/FR language files must have an identical key
 * structure (recursively, including nested arrays). Values are intentionally
 * NOT compared — only the key skeleton.
 */
class LanguageParityTest extends TestCase
{
    private string $enDir;
    private string $frDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enDir = base_path('lang/en');
        $this->frDir = base_path('lang/fr');
    }

    /** Recursively flatten an array's keys into dot notation. */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $full));
            } else {
                $keys[] = $full;
            }
        }

        return $keys;
    }

    /** @return string[] basenames of *.php files in a lang dir */
    private function langFiles(string $dir): array
    {
        $files = glob($dir.DIRECTORY_SEPARATOR.'*.php') ?: [];

        return array_map('basename', $files);
    }

    public function test_every_locale_has_the_same_set_of_files(): void
    {
        $en = $this->langFiles($this->enDir);
        $fr = $this->langFiles($this->frDir);

        $missingInFr = array_diff($en, $fr);
        $missingInEn = array_diff($fr, $en);

        $messages = [];
        foreach ($missingInFr as $f) {
            $messages[] = "file `{$f}` missing in locale `fr`";
        }
        foreach ($missingInEn as $f) {
            $messages[] = "file `{$f}` missing in locale `en`";
        }

        $this->assertSame(
            [],
            $messages,
            "Language file set mismatch:\n - ".implode("\n - ", $messages)
        );
    }

    public function test_en_and_fr_key_structures_match_per_file(): void
    {
        $files = array_unique(array_merge(
            $this->langFiles($this->enDir),
            $this->langFiles($this->frDir)
        ));
        sort($files);

        $problems = [];

        foreach ($files as $file) {
            $enPath = $this->enDir.DIRECTORY_SEPARATOR.$file;
            $frPath = $this->frDir.DIRECTORY_SEPARATOR.$file;

            $en = is_file($enPath) ? require $enPath : null;
            $fr = is_file($frPath) ? require $frPath : null;

            if (! is_array($en) || ! is_array($fr)) {
                // File-set mismatch is asserted by the other test; skip here.
                continue;
            }

            $enKeys = $this->flattenKeys($en);
            $frKeys = $this->flattenKeys($fr);

            foreach (array_diff($enKeys, $frKeys) as $missing) {
                $problems[] = "missing key `{$missing}` | locale `fr` | file `{$file}`";
            }
            foreach (array_diff($frKeys, $enKeys) as $missing) {
                $problems[] = "missing key `{$missing}` | locale `en` | file `{$file}`";
            }
        }

        $this->assertSame(
            [],
            $problems,
            "EN/FR key parity mismatch (".count($problems)." issue(s)):\n - ".implode("\n - ", $problems)
        );
    }
}

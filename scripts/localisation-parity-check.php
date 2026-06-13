<?php

$flatten = function (array $values, string $prefix = '') use (&$flatten): array {
    $result = [];

    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value)) {
            $result += $flatten($value, $path);
        } else {
            $result[$path] = true;
        }
    }

    return $result;
};

$files = array_unique(array_merge(
    array_map('basename', glob(__DIR__ . '/../lang/en/*.php')),
    array_map('basename', glob(__DIR__ . '/../lang/fr/*.php')),
));

$hasDifferences = false;

foreach ($files as $file) {
    $english = file_exists(__DIR__ . '/../lang/en/' . $file)
        ? $flatten(require __DIR__ . '/../lang/en/' . $file)
        : [];
    $french = file_exists(__DIR__ . '/../lang/fr/' . $file)
        ? $flatten(require __DIR__ . '/../lang/fr/' . $file)
        : [];

    $missingEnglish = array_diff_key($french, $english);
    $missingFrench = array_diff_key($english, $french);

    if ($missingEnglish || $missingFrench) {
        $hasDifferences = true;
        printf(
            "%s EN-missing=%d FR-missing=%d\n",
            $file,
            count($missingEnglish),
            count($missingFrench),
        );
    }
}

if (!$hasDifferences) {
    echo "All EN/FR language keys are in parity.\n";
}

exit($hasDifferences ? 1 : 0);

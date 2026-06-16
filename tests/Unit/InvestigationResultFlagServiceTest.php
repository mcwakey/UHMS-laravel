<?php

namespace Tests\Unit;

use App\Services\InvestigationResultFlagService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InvestigationResultFlagServiceTest extends TestCase
{
    #[DataProvider('flagCases')]
    public function test_it_evaluates_common_reference_range_formats(
        string $value,
        string $range,
        ?string $expected,
    ): void {
        $this->assertSame(
            $expected,
            (new InvestigationResultFlagService())->evaluate($value, $range),
        );
    }

    public static function flagCases(): array
    {
        return [
            'normal range' => ['7', '4-10', 'normal'],
            'low range' => ['3.9', '4 - 10', 'low'],
            'high range' => ['10.1', '4 to 10', 'high'],
            'less than normal' => ['4.9', '<5', 'normal'],
            'less than high' => ['5', '<5', 'high'],
            'less than or equal normal' => ['5', '<=5', 'normal'],
            'greater than normal' => ['11', '>10', 'normal'],
            'greater than low' => ['10', '>10', 'low'],
            'maximum phrase' => ['5', 'Maximum 5', 'normal'],
            'minimum phrase' => ['9', 'At least 10', 'low'],
            'text normal' => ['Negative', 'Negative', 'normal'],
            'text abnormal' => ['Positive', 'Negative', 'abnormal'],
            'unsupported mixed range' => ['7', 'Adult male: 4-10', null],
            'blank value' => ['', '4-10', null],
        ];
    }
}

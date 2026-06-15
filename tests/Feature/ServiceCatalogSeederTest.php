<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\ResultType;
use App\Models\Department;
use App\Models\ServiceCatalog;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_investigation_services_are_seeded_with_overall_result_types(): void
    {
        Department::create([
            'name' => 'Laboratory',
            'code' => 'LAB',
            'type' => DepartmentType::INVESTIGATION->value,
            'result_type' => ResultType::PARAMETERS->value,
            'status' => 'active',
        ]);
        Department::create([
            'name' => 'Radiology / X-Ray',
            'code' => 'RAD',
            'type' => DepartmentType::INVESTIGATION->value,
            'result_type' => ResultType::RICHTEXT->value,
            'status' => 'active',
        ]);

        $this->seed(ServiceCatalogSeeder::class);

        $expected = [
            'LAB-FBC' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'LAB-MAL' => [ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE, 'Positive', 'Negative'],
            'LAB-URI' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'LAB-LFT' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'LAB-RFT' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'LAB-BGX' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'LAB-WID' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'LAB-HIV' => [ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE, 'Reactive', 'Non-reactive'],
            'LAB-HBV' => [ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE, 'Reactive', 'Non-reactive'],
            'LAB-HCG' => [ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE, 'Positive', 'Negative'],
            'RAD-CXR' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'RAD-ABX' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'RAD-AUS' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
            'RAD-PUS' => [ServiceCatalog::OVERALL_RESULT_FREE_TEXT, null, null],
        ];

        foreach ($expected as $code => [$type, $positiveLabel, $negativeLabel]) {
            $service = ServiceCatalog::where('code', $code)->firstOrFail();

            $this->assertSame($type, $service->overall_result_type, $code);
            $this->assertSame($positiveLabel, $service->overall_result_positive_label, $code);
            $this->assertSame($negativeLabel, $service->overall_result_negative_label, $code);
        }
    }
}

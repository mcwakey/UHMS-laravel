<?php

namespace Tests\Feature;

use App\Enums\InsuranceType;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ServiceCatalog;
use App\Models\ServicePrice;
use Database\Seeders\InsurancePricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsurancePricingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_type_default_prices_for_services_and_products(): void
    {
        $service = ServiceCatalog::create([
            'name' => 'Seeded Service',
            'code' => 'SEED-SVC',
            'category' => 'consultation',
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);
        $product = Product::create([
            'name' => 'Seeded Product',
            'code' => 'SEED-PRD',
            'product_type' => ProductType::DRUG->value,
            'unit' => 'unit',
            'base_price' => 20,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->seed(InsurancePricingSeeder::class);

        $expectedServicePrices = [
            InsuranceType::SELF->value => '100.00',
            InsuranceType::NHIA->value => '80.00',
            InsuranceType::PRIVATE->value => '90.00',
            InsuranceType::CORPORATE->value => '85.00',
        ];
        $expectedProductPrices = [
            InsuranceType::SELF->value => '20.00',
            InsuranceType::NHIA->value => '16.00',
            InsuranceType::PRIVATE->value => '18.00',
            InsuranceType::CORPORATE->value => '17.00',
        ];

        foreach ($expectedServicePrices as $type => $price) {
            $this->assertSame($price, ServicePrice::where([
                'service_catalog_id' => $service->id,
                'insurance_type' => $type,
            ])->whereNull('insurance_provider_id')->firstOrFail()->price);
        }

        foreach ($expectedProductPrices as $type => $price) {
            $seededPrice = ProductPrice::where([
                'product_id' => $product->id,
                'insurance_type' => $type,
            ])->whereNull('insurance_provider_id')->firstOrFail();

            $this->assertSame($price, $seededPrice->price);
            $this->assertTrue($seededPrice->is_active);
        }
    }

    public function test_it_preserves_existing_custom_prices_when_rerun(): void
    {
        $service = ServiceCatalog::create([
            'name' => 'Custom Service',
            'code' => 'CUSTOM-SVC',
            'category' => 'consultation',
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        ServicePrice::create([
            'service_catalog_id' => $service->id,
            'insurance_type' => InsuranceType::NHIA->value,
            'insurance_provider_id' => null,
            'price' => 72.50,
        ]);

        $this->seed(InsurancePricingSeeder::class);

        $this->assertSame('72.50', ServicePrice::where([
            'service_catalog_id' => $service->id,
            'insurance_type' => InsuranceType::NHIA->value,
        ])->whereNull('insurance_provider_id')->firstOrFail()->price);
    }
}

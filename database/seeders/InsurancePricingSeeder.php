<?php

namespace Database\Seeders;

use App\Enums\InsuranceType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ServiceCatalog;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class InsurancePricingSeeder extends Seeder
{
    /**
     * Starter type-default tariffs expressed as a percentage of the base cash
     * price. Existing configured prices are preserved when this seeder is rerun.
     */
    private const MULTIPLIERS = [
        InsuranceType::SELF->value => 1.00,
        InsuranceType::NHIA->value => 0.80,
        InsuranceType::PRIVATE->value => 0.90,
        InsuranceType::CORPORATE->value => 0.85,
    ];

    public function run(): void
    {
        $servicePrices = 0;
        $productPrices = 0;

        ServiceCatalog::query()
            ->where('is_active', true)
            ->where('is_billable', true)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->eachById(function (ServiceCatalog $service) use (&$servicePrices) {
                foreach (self::MULTIPLIERS as $type => $multiplier) {
                    $price = ServicePrice::firstOrCreate(
                        [
                            'service_catalog_id' => $service->id,
                            'insurance_type' => $type,
                            'insurance_provider_id' => null,
                        ],
                        ['price' => $this->insurancePrice((float) $service->price, $multiplier)]
                    );

                    if ($price->wasRecentlyCreated) {
                        $servicePrices++;
                    }
                }
            });

        Product::query()
            ->where('is_active', true)
            ->where('is_billable', true)
            ->whereNotNull('base_price')
            ->where('base_price', '>', 0)
            ->eachById(function (Product $product) use (&$productPrices) {
                foreach (self::MULTIPLIERS as $type => $multiplier) {
                    $price = ProductPrice::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'insurance_type' => $type,
                            'insurance_provider_id' => null,
                        ],
                        [
                            'price' => $this->insurancePrice((float) $product->base_price, $multiplier),
                            'is_active' => true,
                        ]
                    );

                    if ($price->wasRecentlyCreated) {
                        $productPrices++;
                    }
                }
            });

        $this->command?->info(
            "Seeded {$servicePrices} service prices and {$productPrices} product prices across all insurance types."
        );
    }

    private function insurancePrice(float $basePrice, float $multiplier): float
    {
        return max(0.01, round($basePrice * $multiplier, 2));
    }
}

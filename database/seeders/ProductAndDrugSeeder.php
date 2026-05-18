<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\DrugGenericName;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds Products (the unified stock spine) + Drugs (linked to Products via
 * products.id) + DrugCategories. Also seeds non-drug consumables and a few
 * lab reagents so the Store, Pharmacy and Lab modules all have data on
 * day one.
 */
class ProductAndDrugSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1) Drug categories ─────────────────────────────────────────────
        $categories = [
            'Analgesics' => 'Pain killers and antipyretics',
            'Antibiotics' => 'Antibacterial agents',
            'Antimalarials' => 'Antimalarial agents',
            'Antifungals' => 'Antifungal agents',
            'Antihypertensives' => 'Blood pressure medications',
            'Antidiabetics' => 'Diabetes medications',
            'Gastrointestinal' => 'GI tract medications',
            'Respiratory' => 'Cough, asthma, bronchodilators',
            'Vitamins & Supplements' => 'Vitamins, minerals',
            'IV Fluids' => 'Intravenous fluids',
        ];
        $catMap = [];
        foreach ($categories as $name => $desc) {
            $catMap[$name] = DrugCategory::updateOrCreate(
                ['name' => $name],
                ['description' => $desc, 'is_active' => true]
            )->id;
        }

        // ── 2) Drug catalog: [name, brand, dosage_form, strength, unit, price, cost, opening_stock, reorder, category, generic_name] ──
        $drugs = [
            ['Paracetamol 500mg',        'Panadol',     'tablet',    '500mg',  'tabs', 0.20,  0.10,  2000, 200, 'Analgesics',         'Paracetamol'],
            ['Ibuprofen 400mg',          'Brufen',      'tablet',    '400mg',  'tabs', 0.40,  0.20,  1500, 150, 'Analgesics',         'Ibuprofen'],
            ['Diclofenac 50mg',          'Voltaren',    'tablet',    '50mg',   'tabs', 0.50,  0.25,  1000, 100, 'Analgesics',         'Diclofenac'],
            ['Tramadol 50mg',            'Tramol',      'capsule',   '50mg',   'caps', 0.80,  0.45,   500,  50, 'Analgesics',         'Tramadol'],
            ['Amoxicillin 500mg',        'Amoxil',      'capsule',   '500mg',  'caps', 0.60,  0.30,  1500, 150, 'Antibiotics',        'Amoxicillin'],
            ['Amoxiclav 625mg',          'Augmentin',   'tablet',    '625mg',  'tabs', 1.50,  0.80,   600,  60, 'Antibiotics',        'Amoxicillin + Clavulanic Acid'],
            ['Ciprofloxacin 500mg',      'Ciproxin',    'tablet',    '500mg',  'tabs', 1.20,  0.60,   800,  80, 'Antibiotics',        'Ciprofloxacin'],
            ['Azithromycin 500mg',       'Zithromax',   'tablet',    '500mg',  'tabs', 2.50,  1.20,   500,  50, 'Antibiotics',        'Azithromycin'],
            ['Ceftriaxone 1g Injection', 'Rocephin',    'injection', '1g',     'vial', 8.00,  4.50,   200,  30, 'Antibiotics',        'Ceftriaxone'],
            ['Metronidazole 200mg',      'Flagyl',      'tablet',    '200mg',  'tabs', 0.30,  0.15,  1500, 150, 'Antibiotics',        'Metronidazole'],
            ['Artemether-Lumefantrine',  'Coartem',     'tablet',    '20/120', 'tabs', 5.00,  3.00,   400,  40, 'Antimalarials',      'Artemether + Lumefantrine'],
            ['Artesunate 60mg Injection','Larinate',    'injection', '60mg',   'vial', 25.00, 15.00,  100,  20, 'Antimalarials',      'Artesunate'],
            ['Quinine 300mg',            'Quinimax',    'tablet',    '300mg',  'tabs', 0.80,  0.40,   500,  50, 'Antimalarials',      'Quinine'],
            ['Fluconazole 150mg',        'Diflucan',    'capsule',   '150mg',  'caps', 4.00,  2.00,   200,  20, 'Antifungals',        'Fluconazole'],
            ['Nystatin Oral Suspension', 'Nystan',      'syrup',     '100k IU','btl',  6.00,  3.50,   100,  10, 'Antifungals',        'Nystatin'],
            ['Amlodipine 5mg',           'Norvasc',     'tablet',    '5mg',    'tabs', 0.60,  0.30,  1200, 120, 'Antihypertensives',  null],
            ['Lisinopril 10mg',          'Zestril',     'tablet',    '10mg',   'tabs', 0.80,  0.40,  1000, 100, 'Antihypertensives',  null],
            ['Hydrochlorothiazide 25mg', 'HCTZ',        'tablet',    '25mg',   'tabs', 0.30,  0.15,   800,  80, 'Antihypertensives',  null],
            ['Atenolol 50mg',            'Tenormin',    'tablet',    '50mg',   'tabs', 0.40,  0.20,   800,  80, 'Antihypertensives',  null],
            ['Metformin 500mg',          'Glucophage',  'tablet',    '500mg',  'tabs', 0.50,  0.25,  1500, 150, 'Antidiabetics',      null],
            ['Insulin Mixtard 100IU',    'Mixtard',     'injection', '100IU',  'vial', 60.00, 40.00,   80,  10, 'Antidiabetics',      null],
            ['Omeprazole 20mg',          'Losec',       'capsule',   '20mg',   'caps', 0.70,  0.35,  1000, 100, 'Gastrointestinal',   'Omeprazole'],
            ['Ranitidine 150mg',         'Zantac',      'tablet',    '150mg',  'tabs', 0.40,  0.20,   600,  60, 'Gastrointestinal',   null],
            ['ORS Sachets',              'ORS',         'powder',    '20.5g',  'sct',  1.00,  0.50,  1000, 100, 'Gastrointestinal',   null],
            ['Salbutamol Inhaler 100mcg','Ventolin',    'inhaler',   '100mcg', 'inh',  15.00, 8.00,   100,  10, 'Respiratory',        null],
            ['Salbutamol Syrup',         'Ventolin',    'syrup',     '2mg/5mL','btl',  5.00,  2.50,   200,  20, 'Respiratory',        null],
            ['Cough Linctus',            'Benylin',     'syrup',     '100ml',  'btl',  4.00,  2.00,   300,  30, 'Respiratory',        null],
            ['Vitamin B Complex',        'B-Complex',   'tablet',    null,     'tabs', 0.15,  0.07,  3000, 300, 'Vitamins & Supplements', null],
            ['Folic Acid 5mg',           'Folacin',     'tablet',    '5mg',    'tabs', 0.10,  0.05,  3000, 300, 'Vitamins & Supplements', null],
            ['Ferrous Sulphate 200mg',   'Fersolate',   'tablet',    '200mg',  'tabs', 0.15,  0.07,  2000, 200, 'Vitamins & Supplements', null],
            ['Multivitamin Syrup',       'Multivite',   'syrup',     '100ml',  'btl',  6.00,  3.00,   200,  20, 'Vitamins & Supplements', null],
            ['Normal Saline 0.9% 500mL', 'NS',          'iv_fluid',  '500mL',  'btl',  8.00,  4.50,   400,  50, 'IV Fluids',          null],
            ['Ringer\'s Lactate 500mL',  'RL',          'iv_fluid',  '500mL',  'btl',  9.00,  5.00,   400,  50, 'IV Fluids',          null],
            ['Dextrose 5% 500mL',        'D5W',         'iv_fluid',  '500mL',  'btl',  9.00,  5.00,   400,  50, 'IV Fluids',          null],
            ['Dextrose Saline 500mL',    'DNS',         'iv_fluid',  '500mL',  'btl',  9.50,  5.00,   400,  50, 'IV Fluids',          null],
        ];

        $pharmacyDept = Department::where('code', 'PHR')->first();
        $labDept = Department::where('code', 'LAB')->first();
        $theatreDept = Department::where('code', 'THT')->first();

        foreach ($drugs as [$name, $brand, $form, $strength, $unit, $price, $cost, $stock, $reorder, $cat, $generic]) {
            $code = strtoupper('PRD-' . Str::slug(Str::limit($name, 18, ''), '-'));

            $product = Product::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'product_type' => ProductType::DRUG->value,
                    'unit' => $unit,
                    'reorder_level' => $reorder,
                    'default_cost' => $cost,
                    'base_price' => $price,
                    'is_billable' => true,
                    'is_active' => true,
                ]
            );
            if ($pharmacyDept) {
                $product->departments()->syncWithoutDetaching([$pharmacyDept->id]);
            }

            $genericId = $generic
                ? DrugGenericName::where('name', $generic)->value('id')
                : null;

            Drug::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'category_id' => $catMap[$cat],
                    'generic_name_id' => $genericId,
                    'name' => $name,
                    'generic_name' => $generic,
                    'brand_name' => $brand,
                    'dosage_form' => $form,
                    'strength' => $strength,
                    'unit' => $unit,
                    'price' => $price,
                    'opening_stock' => $stock,
                    'reorder_level' => $reorder,
                    'requires_prescription' => true,
                    'is_active' => true,
                ]
            );
        }

        // ── 3) Non-drug consumables / reagents / supplies ─────────────────
        $nonDrugs = [
            // [name, type, unit, cost, price, billable, reorder, departments]
            ['Surgical Gloves (Latex)',      ProductType::CONSUMABLE,      'pairs', 0.80, 1.50, true,  500, ['PHR', 'THT']],
            ['Examination Gloves',           ProductType::CONSUMABLE,      'pairs', 0.30, 0.60, false, 1000, ['PHR', 'OPD']],
            ['Disposable Syringe 5mL',       ProductType::CONSUMABLE,      'pcs',   0.20, 0.50, true,  1000, ['PHR']],
            ['Disposable Syringe 10mL',      ProductType::CONSUMABLE,      'pcs',   0.30, 0.70, true,  500, ['PHR']],
            ['IV Cannula 18G',               ProductType::CONSUMABLE,      'pcs',   1.00, 2.00, true,  300, ['PHR']],
            ['IV Cannula 22G',               ProductType::CONSUMABLE,      'pcs',   1.00, 2.00, true,  300, ['PHR']],
            ['Cotton Wool 100g',             ProductType::CONSUMABLE,      'rolls', 1.20, 2.50, true,  100, ['PHR', 'OPD']],
            ['Gauze Roll 4inch',             ProductType::CONSUMABLE,      'rolls', 1.50, 3.00, true,  100, ['PHR']],
            ['Surgical Mask',                ProductType::CONSUMABLE,      'pcs',   0.20, 0.40, false, 1000, ['PHR']],
            ['Face Shield',                  ProductType::CONSUMABLE,      'pcs',   2.00, 4.00, false, 100, ['PHR']],
            ['Suture (Vicryl 2-0)',          ProductType::SURGICAL_SUPPLY, 'pcs',   8.00, 15.00, true, 100, ['THT']],
            ['Scalpel Blade No.11',          ProductType::SURGICAL_SUPPLY, 'pcs',   1.00, 2.00,  true, 100, ['THT']],
            ['Sterile Drape',                ProductType::SURGICAL_SUPPLY, 'pcs',   3.00, 6.00,  true, 100, ['THT']],

            ['CBC Reagent (Sysmex)',         ProductType::REAGENT,         'pack',  150.00, null, false, 10, ['LAB']],
            ['HBA1C Reagent Kit',            ProductType::REAGENT,         'kit',   180.00, null, false, 5,  ['LAB']],
            ['Malaria RDT Kit',              ProductType::REAGENT,         'kit',    35.00, 40.00, true, 50, ['LAB']],
            ['HIV Test Kit (Determine)',     ProductType::REAGENT,         'kit',    40.00, 50.00, true, 50, ['LAB']],
            ['Urine Strip (10 parameters)',  ProductType::REAGENT,         'strip',   1.50, null, false, 200, ['LAB']],
        ];

        foreach ($nonDrugs as [$name, $type, $unit, $cost, $price, $billable, $reorder, $deptCodes]) {
            $code = strtoupper('PRD-' . Str::slug(Str::limit($name, 18, ''), '-'));
            $product = Product::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'product_type' => $type->value,
                    'unit' => $unit,
                    'reorder_level' => $reorder,
                    'default_cost' => $cost,
                    'base_price' => $price,
                    'is_billable' => $billable,
                    'is_active' => true,
                ]
            );
            $deptIds = Department::whereIn('code', $deptCodes)->pluck('id')->all();
            if (!empty($deptIds)) {
                $product->departments()->syncWithoutDetaching($deptIds);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Ernest Chemists Ltd',     'contact_person' => 'Mr. Boateng', 'phone' => '0302-666-000', 'email' => 'sales@ernestchemists.com.gh', 'address' => 'Industrial Area, Accra'],
            ['name' => 'Kinapharma Limited',      'contact_person' => 'Ms. Owusu',   'phone' => '0302-678-100', 'email' => 'orders@kinapharma.com',       'address' => 'Tema'],
            ['name' => 'Tobinco Pharmaceuticals', 'contact_person' => 'Mr. Tobin',   'phone' => '0302-815-200', 'email' => 'sales@tobinco.com',           'address' => 'North Industrial Area, Accra'],
            ['name' => 'M&G Pharmaceuticals',     'contact_person' => 'Ms. Mensah',  'phone' => '0302-712-300', 'email' => 'info@mgpharma.com',           'address' => 'Spintex, Accra'],
            ['name' => 'Phyto-Riker (GIHOC)',     'contact_person' => 'Mr. Riker',   'phone' => '0302-771-400', 'email' => 'contact@phytoriker.com',      'address' => 'Dome, Accra'],
            ['name' => 'Letap Pharmaceuticals',   'contact_person' => 'Mr. Adjei',   'phone' => '0302-987-500', 'email' => 'sales@letap.com.gh',          'address' => 'Tema Light Industrial Area'],
            ['name' => 'Bio-Rad Diagnostics',     'contact_person' => 'Dr. Sarpong', 'phone' => '0303-100-600', 'email' => 'gh@bio-rad.com',              'address' => 'Airport Residential Area, Accra'],
            ['name' => 'GlobalMed Surgical',      'contact_person' => 'Mr. Quaye',   'phone' => '0302-555-700', 'email' => 'orders@globalmed.com',        'address' => 'Ring Road East, Accra'],
        ];

        foreach ($suppliers as $row) {
            Supplier::updateOrCreate(
                ['name' => $row['name']],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}

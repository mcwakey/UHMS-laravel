<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DepartmentSeeder::class,
            AdminUserSeeder::class,
            CashAndCarrySeeder::class,
            ServiceCatalogSeeder::class,
            DesignationSeeder::class,
            SpecialtySeeder::class,
            IcdCodeSeeder::class,
            DemoUserSeeder::class,
            PatientSeeder::class,
        ]);
    }
}

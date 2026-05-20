<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // Core modules — cannot be disabled
            ['name' => 'Authentication',   'slug' => 'auth',           'is_core' => true,  'icon' => 'ti-lock',          'sort_order' => 1],
            ['name' => 'Users & Roles',    'slug' => 'users',          'is_core' => true,  'icon' => 'ti-users',         'sort_order' => 2],
            ['name' => 'Patients',         'slug' => 'patients',       'is_core' => true,  'icon' => 'ti-user-heart',    'sort_order' => 3],
            ['name' => 'Visits',           'slug' => 'visits',         'is_core' => true,  'icon' => 'ti-stethoscope',   'sort_order' => 4],
            ['name' => 'Triage',           'slug' => 'triage',         'is_core' => true,  'icon' => 'ti-heartbeat',     'sort_order' => 5],
            ['name' => 'Consultation',     'slug' => 'consultation',   'is_core' => true,  'icon' => 'ti-clipboard-text', 'sort_order' => 6],
            ['name' => 'Departments',      'slug' => 'departments',    'is_core' => true,  'icon' => 'ti-building-hospital', 'sort_order' => 7],
            ['name' => 'Services',         'slug' => 'services',       'is_core' => true,  'icon' => 'ti-list-details',  'sort_order' => 8],
            ['name' => 'Billing',          'slug' => 'billing',        'is_core' => true,  'icon' => 'ti-receipt',       'sort_order' => 9],
            ['name' => 'Settings',         'slug' => 'settings',       'is_core' => true,  'icon' => 'ti-settings',      'sort_order' => 10],

            // Optional modules — can be disabled
            ['name' => 'Insurance',        'slug' => 'insurance',      'is_core' => false, 'icon' => 'ti-shield-check',  'sort_order' => 20],
            ['name' => 'Claims',           'slug' => 'claims',         'is_core' => false, 'depends_on' => 'insurance', 'icon' => 'ti-file-dollar', 'sort_order' => 21],
            ['name' => 'Pharmacy',         'slug' => 'pharmacy',       'is_core' => false, 'icon' => 'ti-pill',          'sort_order' => 22],
            ['name' => 'Inventory/Store',  'slug' => 'inventory',      'is_core' => false, 'icon' => 'ti-packages',      'sort_order' => 23],
            ['name' => 'Investigations',   'slug' => 'investigations', 'is_core' => false, 'icon' => 'ti-microscope',    'sort_order' => 24],
            ['name' => 'Analyzer',         'slug' => 'analyzer',       'is_core' => false, 'depends_on' => 'investigations', 'icon' => 'ti-device-analytics', 'sort_order' => 25],
            ['name' => 'HR',               'slug' => 'hr',             'is_core' => false, 'icon' => 'ti-id-badge',      'sort_order' => 26],
            ['name' => 'Payroll',          'slug' => 'payroll',        'is_core' => false, 'depends_on' => 'hr',         'icon' => 'ti-cash',  'sort_order' => 27],
            ['name' => 'Reports',          'slug' => 'reports',        'is_core' => false, 'icon' => 'ti-chart-bar',     'sort_order' => 28],
            ['name' => 'Medical Patterns', 'slug' => 'medical-patterns', 'is_core' => false, 'icon' => 'ti-template',    'sort_order' => 29],
            ['name' => 'Notifications',    'slug' => 'notifications',  'is_core' => false, 'icon' => 'ti-bell',          'sort_order' => 30],
            ['name' => 'Emergency Unit',   'slug' => 'emergency',      'is_core' => false, 'depends_on' => 'visits',     'icon' => 'ti-ambulance',     'sort_order' => 31],
        ];

        foreach ($modules as $m) {
            Module::updateOrCreate(
                ['slug' => $m['slug']],
                array_merge(['is_enabled' => true], $m)
            );
        }
    }
}

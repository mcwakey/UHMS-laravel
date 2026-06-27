<?php

namespace Database\Seeders\ManualTesting;

class ManualDepartmentSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('departments')) {
            return;
        }

        $departments = [
            ['General OPD', 'consultation'], ['Emergency / Casualty', 'emergency'], ['Antenatal Clinic', 'consultation'],
            ['Paediatric Consultation', 'consultation'], ['Surgical Consultation', 'consultation'], ['Specialist Clinic', 'consultation'],
            ['Main Laboratory', 'investigation'], ['Haematology', 'investigation'], ['Biochemistry', 'investigation'],
            ['Microbiology', 'investigation'], ['Serology', 'investigation'], ['X-Ray Unit', 'radiology'],
            ['Ultrasound Unit', 'radiology'], ['CT Scan Unit', 'radiology'], ['Imaging Reporting Unit', 'radiology'],
            ['Main Theatre', 'theatre'], ['Minor Procedure Room', 'procedure'], ['Endoscopy Unit', 'procedure'],
            ['Dressing Room', 'procedure'], ['Male Ward', 'inpatient'], ['Female Ward', 'inpatient'],
            ['Paediatric Ward', 'inpatient'], ['Maternity Ward', 'maternity'], ['ICU', 'inpatient'],
            ['Recovery Unit', 'nursing'], ['Main Pharmacy', 'pharmacy'], ['Emergency Pharmacy', 'pharmacy'],
            ['Ward Pharmacy', 'pharmacy'], ['Store Pharmacy', 'stores'], ['CSSD', 'support'],
            ['Biomedical Engineering', 'support'], ['Housekeeping', 'support'], ['Maintenance', 'support'],
            ['Records Office', 'records'], ['Security', 'support'], ['Accounts', 'finance'],
            ['Billing Office', 'finance'], ['Claims Office', 'administrative'], ['HR Office', 'administrative'],
            ['Procurement', 'stores'], ['Management', 'administrative'], ['Reception', 'records'],
        ];

        $limit = min($this->target('departments'), count($departments));
        foreach (array_slice($departments, 0, $limit) as $index => [$name, $type]) {
            $code = $this->ref('DEP', $index + 1, 3);
            $this->updateOrInsert('departments', ['code' => $code], [
                'name' => $name,
                'code' => $code,
                'description' => "{$name} Dashboard - manual test department for {$type} workflows.",
                'type' => $type,
                'status' => 'active',
                'result_type' => in_array($type, ['investigation', 'radiology'], true) ? 'structured' : 'none',
                'is_stock_managed' => in_array($type, ['pharmacy', 'stores', 'theatre', 'procedure', 'investigation'], true),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }
}

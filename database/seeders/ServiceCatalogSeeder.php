<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ServiceCatalog;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Map department names to their services
        $departmentServices = [
            'General Medicine / OPD' => [
                ['name' => 'General Consultation', 'code' => 'OPD-CON', 'category' => 'consultation', 'price' => 100.00],
                ['name' => 'Follow-up Consultation', 'code' => 'OPD-FUP', 'category' => 'consultation', 'price' => 60.00],
                ['name' => 'Blood Pressure Check', 'code' => 'OPD-BPC', 'category' => 'procedure', 'price' => 20.00],
                ['name' => 'Blood Glucose Test', 'code' => 'OPD-BGT', 'category' => 'procedure', 'price' => 30.00],
                ['name' => 'Wound Dressing', 'code' => 'OPD-WD', 'category' => 'procedure', 'price' => 50.00],
                ['name' => 'Injection Administration', 'code' => 'OPD-INJ', 'category' => 'procedure', 'price' => 30.00],
                ['name' => 'Medical Certificate', 'code' => 'OPD-MC', 'category' => 'admin', 'price' => 50.00],
            ],
            'Pediatrics' => [
                ['name' => 'Pediatric Consultation', 'code' => 'PED-CON', 'category' => 'consultation', 'price' => 120.00],
                ['name' => 'Child Immunization', 'code' => 'PED-IMM', 'category' => 'procedure', 'price' => 80.00],
                ['name' => 'Growth Monitoring', 'code' => 'PED-GM', 'category' => 'procedure', 'price' => 30.00],
                ['name' => 'Neonatal Assessment', 'code' => 'PED-NEO', 'category' => 'procedure', 'price' => 150.00],
            ],
            'Obstetrics & Gynecology' => [
                ['name' => 'Obstetric Consultation', 'code' => 'OBG-CON', 'category' => 'consultation', 'price' => 150.00],
                ['name' => 'Antenatal Check-up', 'code' => 'OBG-ANC', 'category' => 'procedure', 'price' => 80.00],
                ['name' => 'Ultrasound Scan (Obstetric)', 'code' => 'OBG-USS', 'category' => 'imaging', 'price' => 200.00],
                ['name' => 'Pap Smear', 'code' => 'OBG-PAP', 'category' => 'procedure', 'price' => 120.00],
                ['name' => 'Family Planning Consultation', 'code' => 'OBG-FPC', 'category' => 'consultation', 'price' => 50.00],
            ],
            'Surgery' => [
                ['name' => 'Surgical Consultation', 'code' => 'SUR-CON', 'category' => 'consultation', 'price' => 200.00],
                ['name' => 'Minor Surgery', 'code' => 'SUR-MIN', 'category' => 'surgery', 'price' => 500.00],
                ['name' => 'Incision & Drainage', 'code' => 'SUR-IND', 'category' => 'surgery', 'price' => 300.00],
                ['name' => 'Suturing', 'code' => 'SUR-SUT', 'category' => 'procedure', 'price' => 150.00],
            ],
            'Orthopedics' => [
                ['name' => 'Orthopedic Consultation', 'code' => 'ORT-CON', 'category' => 'consultation', 'price' => 200.00],
                ['name' => 'Plaster of Paris (POP) Application', 'code' => 'ORT-POP', 'category' => 'procedure', 'price' => 300.00],
                ['name' => 'Fracture Management', 'code' => 'ORT-FRC', 'category' => 'procedure', 'price' => 400.00],
            ],
            'Eye Clinic' => [
                ['name' => 'Eye Consultation', 'code' => 'EYE-CON', 'category' => 'consultation', 'price' => 150.00],
                ['name' => 'Visual Acuity Test', 'code' => 'EYE-VAT', 'category' => 'procedure', 'price' => 50.00],
                ['name' => 'Eye Drop Administration', 'code' => 'EYE-ED', 'category' => 'procedure', 'price' => 30.00],
                ['name' => 'Foreign Body Removal (Eye)', 'code' => 'EYE-FBR', 'category' => 'procedure', 'price' => 100.00],
            ],
            'ENT' => [
                ['name' => 'ENT Consultation', 'code' => 'ENT-CON', 'category' => 'consultation', 'price' => 150.00],
                ['name' => 'Ear Syringing', 'code' => 'ENT-SYR', 'category' => 'procedure', 'price' => 80.00],
                ['name' => 'Throat Swab', 'code' => 'ENT-TSW', 'category' => 'procedure', 'price' => 50.00],
            ],
            'Dental' => [
                ['name' => 'Dental Consultation', 'code' => 'DEN-CON', 'category' => 'consultation', 'price' => 120.00],
                ['name' => 'Tooth Extraction', 'code' => 'DEN-EXT', 'category' => 'procedure', 'price' => 200.00],
                ['name' => 'Dental Scaling & Polish', 'code' => 'DEN-SP', 'category' => 'procedure', 'price' => 250.00],
                ['name' => 'Dental Filling', 'code' => 'DEN-FIL', 'category' => 'procedure', 'price' => 300.00],
            ],
            'Psychiatry' => [
                ['name' => 'Psychiatric Consultation', 'code' => 'PSY-CON', 'category' => 'consultation', 'price' => 200.00],
                ['name' => 'Counseling Session', 'code' => 'PSY-CNS', 'category' => 'consultation', 'price' => 150.00],
            ],
            'Emergency / Casualty' => [
                ['name' => 'Emergency Consultation', 'code' => 'EMR-CON', 'category' => 'consultation', 'price' => 250.00],
                ['name' => 'Emergency Triage', 'code' => 'EMR-TRI', 'category' => 'procedure', 'price' => 50.00],
                ['name' => 'Resuscitation', 'code' => 'EMR-RES', 'category' => 'procedure', 'price' => 500.00],
                ['name' => 'Oxygen Therapy', 'code' => 'EMR-OXY', 'category' => 'procedure', 'price' => 200.00],
            ],
            'Laboratory' => [
                ['name' => 'Full Blood Count (FBC)', 'code' => 'LAB-FBC', 'category' => 'lab', 'price' => 80.00],
                ['name' => 'Malaria RDT', 'code' => 'LAB-MAL', 'category' => 'lab', 'price' => 40.00],
                ['name' => 'Urinalysis', 'code' => 'LAB-URI', 'category' => 'lab', 'price' => 50.00],
                ['name' => 'Liver Function Test', 'code' => 'LAB-LFT', 'category' => 'lab', 'price' => 120.00],
                ['name' => 'Renal Function Test', 'code' => 'LAB-RFT', 'category' => 'lab', 'price' => 120.00],
                ['name' => 'Blood Group & Cross Match', 'code' => 'LAB-BGX', 'category' => 'lab', 'price' => 80.00],
                ['name' => 'Widal Test', 'code' => 'LAB-WID', 'category' => 'lab', 'price' => 60.00],
                ['name' => 'HIV Screening', 'code' => 'LAB-HIV', 'category' => 'lab', 'price' => 50.00],
                ['name' => 'Hepatitis B Test', 'code' => 'LAB-HBV', 'category' => 'lab', 'price' => 60.00],
                ['name' => 'Pregnancy Test (HCG)', 'code' => 'LAB-HCG', 'category' => 'lab', 'price' => 40.00],
            ],
            'Pharmacy' => [
                ['name' => 'Dispensing Fee', 'code' => 'PHM-DSP', 'category' => 'pharmacy', 'price' => 10.00],
            ],
            'Radiology / X-Ray' => [
                ['name' => 'Chest X-Ray', 'code' => 'RAD-CXR', 'category' => 'imaging', 'price' => 150.00],
                ['name' => 'Abdominal X-Ray', 'code' => 'RAD-ABX', 'category' => 'imaging', 'price' => 150.00],
                ['name' => 'Abdominal Ultrasound', 'code' => 'RAD-AUS', 'category' => 'imaging', 'price' => 200.00],
                ['name' => 'Pelvic Ultrasound', 'code' => 'RAD-PUS', 'category' => 'imaging', 'price' => 200.00],
            ],
            'Physiotherapy' => [
                ['name' => 'Physiotherapy Consultation', 'code' => 'PHT-CON', 'category' => 'consultation', 'price' => 120.00],
                ['name' => 'Physiotherapy Session', 'code' => 'PHT-SES', 'category' => 'procedure', 'price' => 100.00],
            ],
            'Antenatal / Postnatal' => [
                ['name' => 'Antenatal Registration', 'code' => 'ANC-REG', 'category' => 'admin', 'price' => 50.00],
                ['name' => 'Antenatal Visit', 'code' => 'ANC-VIS', 'category' => 'consultation', 'price' => 80.00],
                ['name' => 'Postnatal Check-up', 'code' => 'PNC-CHK', 'category' => 'consultation', 'price' => 80.00],
            ],
            'Family Planning' => [
                ['name' => 'Family Planning Counseling', 'code' => 'FPL-CNS', 'category' => 'consultation', 'price' => 50.00],
                ['name' => 'Implant Insertion', 'code' => 'FPL-IMP', 'category' => 'procedure', 'price' => 200.00],
                ['name' => 'IUD Insertion', 'code' => 'FPL-IUD', 'category' => 'procedure', 'price' => 150.00],
                ['name' => 'Injectable Contraceptive', 'code' => 'FPL-INJ', 'category' => 'procedure', 'price' => 50.00],
            ],
            'Theatre / Procedures' => [
                ['name' => 'Appendicectomy', 'code' => 'THT-APP', 'category' => 'surgery', 'price' => 2500.00],
                ['name' => 'Caesarean Section (C/S)', 'code' => 'THT-CS', 'category' => 'surgery', 'price' => 3000.00],
                ['name' => 'Hernia Repair', 'code' => 'THT-HRN', 'category' => 'surgery', 'price' => 2000.00],
                ['name' => 'Laparotomy', 'code' => 'THT-LAP', 'category' => 'surgery', 'price' => 3500.00],
                ['name' => 'Myomectomy', 'code' => 'THT-MYO', 'category' => 'surgery', 'price' => 3000.00],
                ['name' => 'Hysterectomy', 'code' => 'THT-HYS', 'category' => 'surgery', 'price' => 4000.00],
                ['name' => 'Prostatectomy', 'code' => 'THT-PRO', 'category' => 'surgery', 'price' => 4500.00],
                ['name' => 'Cholecystectomy', 'code' => 'THT-CHO', 'category' => 'surgery', 'price' => 3000.00],
                ['name' => 'Thyroidectomy', 'code' => 'THT-THY', 'category' => 'surgery', 'price' => 3500.00],
                ['name' => 'Mastectomy', 'code' => 'THT-MAS', 'category' => 'surgery', 'price' => 3500.00],
                ['name' => 'Colostomy', 'code' => 'THT-COL', 'category' => 'surgery', 'price' => 3000.00],
                ['name' => 'Inguinal Herniorrhaphy', 'code' => 'THT-IHR', 'category' => 'surgery', 'price' => 2200.00],
                ['name' => 'ORIF (Fracture Fixation)', 'code' => 'THT-ORI', 'category' => 'surgery', 'price' => 4000.00],
                ['name' => 'Exploratory Laparotomy', 'code' => 'THT-EXL', 'category' => 'surgery', 'price' => 3500.00],
                ['name' => 'General Anaesthesia', 'code' => 'THT-GAN', 'category' => 'anaesthesia', 'price' => 800.00],
                ['name' => 'Spinal Anaesthesia', 'code' => 'THT-SPA', 'category' => 'anaesthesia', 'price' => 500.00],
                ['name' => 'Theatre Fee', 'code' => 'THT-FEE', 'category' => 'procedure', 'price' => 300.00],
            ],
        ];

        foreach ($departmentServices as $deptName => $services) {
            $dept = Department::where('name', $deptName)->first();
            if (!$dept) {
                continue;
            }

            foreach ($services as $svc) {
                ServiceCatalog::updateOrCreate(
                    ['code' => $svc['code']],
                    [
                        'name' => $svc['name'],
                        'category' => $svc['category'],
                        'price' => $svc['price'],
                        'department_id' => $dept->id,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Update the existing 'general consultation' to have the OPD department if it still has no dept
        $opd = Department::where('name', 'General Medicine / OPD')->first();
        if ($opd) {
            ServiceCatalog::where('code', 'GC')
                ->whereNull('department_id')
                ->update(['department_id' => $opd->id]);
        }

        $this->command->info('Seeded ' . ServiceCatalog::count() . ' services across ' . count($departmentServices) . ' departments.');
    }
}

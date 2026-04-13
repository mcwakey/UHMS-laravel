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
                ['name' => 'General Consultation', 'code' => 'OPD-CON', 'category' => 'consultation', 'price' => 100.00, 'nhis_price' => 50.00, 'is_nhis_covered' => true],
                ['name' => 'Follow-up Consultation', 'code' => 'OPD-FUP', 'category' => 'consultation', 'price' => 60.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'Blood Pressure Check', 'code' => 'OPD-BPC', 'category' => 'procedure', 'price' => 20.00, 'nhis_price' => 15.00, 'is_nhis_covered' => true],
                ['name' => 'Blood Glucose Test', 'code' => 'OPD-BGT', 'category' => 'procedure', 'price' => 30.00, 'nhis_price' => 20.00, 'is_nhis_covered' => true],
                ['name' => 'Wound Dressing', 'code' => 'OPD-WD', 'category' => 'procedure', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'Injection Administration', 'code' => 'OPD-INJ', 'category' => 'procedure', 'price' => 30.00, 'nhis_price' => 15.00, 'is_nhis_covered' => true],
                ['name' => 'Medical Certificate', 'code' => 'OPD-MC', 'category' => 'admin', 'price' => 50.00, 'nhis_price' => null, 'is_nhis_covered' => false],
            ],
            'Pediatrics' => [
                ['name' => 'Pediatric Consultation', 'code' => 'PED-CON', 'category' => 'consultation', 'price' => 120.00, 'nhis_price' => 60.00, 'is_nhis_covered' => true],
                ['name' => 'Child Immunization', 'code' => 'PED-IMM', 'category' => 'procedure', 'price' => 80.00, 'nhis_price' => 40.00, 'is_nhis_covered' => true],
                ['name' => 'Growth Monitoring', 'code' => 'PED-GM', 'category' => 'procedure', 'price' => 30.00, 'nhis_price' => 20.00, 'is_nhis_covered' => true],
                ['name' => 'Neonatal Assessment', 'code' => 'PED-NEO', 'category' => 'procedure', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
            ],
            'Obstetrics & Gynecology' => [
                ['name' => 'Obstetric Consultation', 'code' => 'OBG-CON', 'category' => 'consultation', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
                ['name' => 'Antenatal Check-up', 'code' => 'OBG-ANC', 'category' => 'procedure', 'price' => 80.00, 'nhis_price' => 50.00, 'is_nhis_covered' => true],
                ['name' => 'Ultrasound Scan (Obstetric)', 'code' => 'OBG-USS', 'category' => 'imaging', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
                ['name' => 'Pap Smear', 'code' => 'OBG-PAP', 'category' => 'procedure', 'price' => 120.00, 'nhis_price' => 60.00, 'is_nhis_covered' => true],
                ['name' => 'Family Planning Consultation', 'code' => 'OBG-FPC', 'category' => 'consultation', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
            ],
            'Surgery' => [
                ['name' => 'Surgical Consultation', 'code' => 'SUR-CON', 'category' => 'consultation', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
                ['name' => 'Minor Surgery', 'code' => 'SUR-MIN', 'category' => 'surgery', 'price' => 500.00, 'nhis_price' => 250.00, 'is_nhis_covered' => true],
                ['name' => 'Incision & Drainage', 'code' => 'SUR-IND', 'category' => 'surgery', 'price' => 300.00, 'nhis_price' => 150.00, 'is_nhis_covered' => true],
                ['name' => 'Suturing', 'code' => 'SUR-SUT', 'category' => 'procedure', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
            ],
            'Orthopedics' => [
                ['name' => 'Orthopedic Consultation', 'code' => 'ORT-CON', 'category' => 'consultation', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
                ['name' => 'Plaster of Paris (POP) Application', 'code' => 'ORT-POP', 'category' => 'procedure', 'price' => 300.00, 'nhis_price' => 150.00, 'is_nhis_covered' => true],
                ['name' => 'Fracture Management', 'code' => 'ORT-FRC', 'category' => 'procedure', 'price' => 400.00, 'nhis_price' => 200.00, 'is_nhis_covered' => true],
            ],
            'Eye Clinic' => [
                ['name' => 'Eye Consultation', 'code' => 'EYE-CON', 'category' => 'consultation', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
                ['name' => 'Visual Acuity Test', 'code' => 'EYE-VAT', 'category' => 'procedure', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'Eye Drop Administration', 'code' => 'EYE-ED', 'category' => 'procedure', 'price' => 30.00, 'nhis_price' => 15.00, 'is_nhis_covered' => true],
                ['name' => 'Foreign Body Removal (Eye)', 'code' => 'EYE-FBR', 'category' => 'procedure', 'price' => 100.00, 'nhis_price' => 50.00, 'is_nhis_covered' => true],
            ],
            'ENT' => [
                ['name' => 'ENT Consultation', 'code' => 'ENT-CON', 'category' => 'consultation', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
                ['name' => 'Ear Syringing', 'code' => 'ENT-SYR', 'category' => 'procedure', 'price' => 80.00, 'nhis_price' => 40.00, 'is_nhis_covered' => true],
                ['name' => 'Throat Swab', 'code' => 'ENT-TSW', 'category' => 'procedure', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
            ],
            'Dental' => [
                ['name' => 'Dental Consultation', 'code' => 'DEN-CON', 'category' => 'consultation', 'price' => 120.00, 'nhis_price' => 60.00, 'is_nhis_covered' => true],
                ['name' => 'Tooth Extraction', 'code' => 'DEN-EXT', 'category' => 'procedure', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
                ['name' => 'Dental Scaling & Polish', 'code' => 'DEN-SP', 'category' => 'procedure', 'price' => 250.00, 'nhis_price' => null, 'is_nhis_covered' => false],
                ['name' => 'Dental Filling', 'code' => 'DEN-FIL', 'category' => 'procedure', 'price' => 300.00, 'nhis_price' => 150.00, 'is_nhis_covered' => true],
            ],
            'Psychiatry' => [
                ['name' => 'Psychiatric Consultation', 'code' => 'PSY-CON', 'category' => 'consultation', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
                ['name' => 'Counseling Session', 'code' => 'PSY-CNS', 'category' => 'consultation', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
            ],
            'Emergency / Casualty' => [
                ['name' => 'Emergency Consultation', 'code' => 'EMR-CON', 'category' => 'consultation', 'price' => 250.00, 'nhis_price' => 120.00, 'is_nhis_covered' => true],
                ['name' => 'Emergency Triage', 'code' => 'EMR-TRI', 'category' => 'procedure', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'Resuscitation', 'code' => 'EMR-RES', 'category' => 'procedure', 'price' => 500.00, 'nhis_price' => 250.00, 'is_nhis_covered' => true],
                ['name' => 'Oxygen Therapy', 'code' => 'EMR-OXY', 'category' => 'procedure', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
            ],
            'Laboratory' => [
                ['name' => 'Full Blood Count (FBC)', 'code' => 'LAB-FBC', 'category' => 'lab', 'price' => 80.00, 'nhis_price' => 40.00, 'is_nhis_covered' => true],
                ['name' => 'Malaria RDT', 'code' => 'LAB-MAL', 'category' => 'lab', 'price' => 40.00, 'nhis_price' => 20.00, 'is_nhis_covered' => true],
                ['name' => 'Urinalysis', 'code' => 'LAB-URI', 'category' => 'lab', 'price' => 50.00, 'nhis_price' => 25.00, 'is_nhis_covered' => true],
                ['name' => 'Liver Function Test', 'code' => 'LAB-LFT', 'category' => 'lab', 'price' => 120.00, 'nhis_price' => 60.00, 'is_nhis_covered' => true],
                ['name' => 'Renal Function Test', 'code' => 'LAB-RFT', 'category' => 'lab', 'price' => 120.00, 'nhis_price' => 60.00, 'is_nhis_covered' => true],
                ['name' => 'Blood Group & Cross Match', 'code' => 'LAB-BGX', 'category' => 'lab', 'price' => 80.00, 'nhis_price' => 40.00, 'is_nhis_covered' => true],
                ['name' => 'Widal Test', 'code' => 'LAB-WID', 'category' => 'lab', 'price' => 60.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'HIV Screening', 'code' => 'LAB-HIV', 'category' => 'lab', 'price' => 50.00, 'nhis_price' => 25.00, 'is_nhis_covered' => true],
                ['name' => 'Hepatitis B Test', 'code' => 'LAB-HBV', 'category' => 'lab', 'price' => 60.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'Pregnancy Test (HCG)', 'code' => 'LAB-HCG', 'category' => 'lab', 'price' => 40.00, 'nhis_price' => 20.00, 'is_nhis_covered' => true],
            ],
            'Pharmacy' => [
                ['name' => 'Dispensing Fee', 'code' => 'PHM-DSP', 'category' => 'pharmacy', 'price' => 10.00, 'nhis_price' => 5.00, 'is_nhis_covered' => true],
            ],
            'Radiology / X-Ray' => [
                ['name' => 'Chest X-Ray', 'code' => 'RAD-CXR', 'category' => 'imaging', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
                ['name' => 'Abdominal X-Ray', 'code' => 'RAD-ABX', 'category' => 'imaging', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
                ['name' => 'Abdominal Ultrasound', 'code' => 'RAD-AUS', 'category' => 'imaging', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
                ['name' => 'Pelvic Ultrasound', 'code' => 'RAD-PUS', 'category' => 'imaging', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
            ],
            'Physiotherapy' => [
                ['name' => 'Physiotherapy Consultation', 'code' => 'PHT-CON', 'category' => 'consultation', 'price' => 120.00, 'nhis_price' => 60.00, 'is_nhis_covered' => true],
                ['name' => 'Physiotherapy Session', 'code' => 'PHT-SES', 'category' => 'procedure', 'price' => 100.00, 'nhis_price' => 50.00, 'is_nhis_covered' => true],
            ],
            'Antenatal / Postnatal' => [
                ['name' => 'Antenatal Registration', 'code' => 'ANC-REG', 'category' => 'admin', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'Antenatal Visit', 'code' => 'ANC-VIS', 'category' => 'consultation', 'price' => 80.00, 'nhis_price' => 50.00, 'is_nhis_covered' => true],
                ['name' => 'Postnatal Check-up', 'code' => 'PNC-CHK', 'category' => 'consultation', 'price' => 80.00, 'nhis_price' => 50.00, 'is_nhis_covered' => true],
            ],
            'Family Planning' => [
                ['name' => 'Family Planning Counseling', 'code' => 'FPL-CNS', 'category' => 'consultation', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
                ['name' => 'Implant Insertion', 'code' => 'FPL-IMP', 'category' => 'procedure', 'price' => 200.00, 'nhis_price' => 100.00, 'is_nhis_covered' => true],
                ['name' => 'IUD Insertion', 'code' => 'FPL-IUD', 'category' => 'procedure', 'price' => 150.00, 'nhis_price' => 80.00, 'is_nhis_covered' => true],
                ['name' => 'Injectable Contraceptive', 'code' => 'FPL-INJ', 'category' => 'procedure', 'price' => 50.00, 'nhis_price' => 30.00, 'is_nhis_covered' => true],
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
                        'nhis_price' => $svc['nhis_price'],
                        'is_nhis_covered' => $svc['is_nhis_covered'],
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

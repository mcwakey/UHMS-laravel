<?php

namespace Database\Seeders;

use App\Models\Analyzer;
use App\Models\AnalyzerTestMapping;
use App\Models\LabTest;
use Illuminate\Database\Seeder;

/**
 * Seeds lab instruments + their per-test code mapping table. Both analyzers
 * are inactive by default; an admin will flip `is_active` and supply real
 * network details before the LIS bridge tries to connect.
 *
 * The analyzer_test_code → lab_tests linkage uses the existing LabCatalog
 * codes seeded by LabCatalogSeeder (LAB-FBC, LAB-LFT, LAB-RFT, LAB-LIP,
 * LAB-FBS). The mapping is keyed (analyzer_id, analyzer_test_code) UNIQUE.
 */
class AnalyzerSeeder extends Seeder
{
    public function run(): void
    {
        $sysmex = Analyzer::updateOrCreate(
            ['name' => 'Sysmex XN-330'],
            [
                'model' => 'XN-330',
                'manufacturer' => 'Sysmex Corporation',
                'protocol' => 'hl7',
                'connection_type' => 'tcp',
                'ip_address' => '192.168.10.50',
                'port' => 5100,
                'is_active' => false,
            ]
        );

        $mindray = Analyzer::updateOrCreate(
            ['name' => 'Mindray BS-240'],
            [
                'model' => 'BS-240',
                'manufacturer' => 'Mindray Medical',
                'protocol' => 'hl7',
                'connection_type' => 'tcp',
                'ip_address' => '192.168.10.51',
                'port' => 5101,
                'is_active' => false,
            ]
        );

        $cobas = Analyzer::updateOrCreate(
            ['name' => 'Roche Cobas u411'],
            [
                'model' => 'u411',
                'manufacturer' => 'Roche Diagnostics',
                'protocol' => 'astm',
                'connection_type' => 'serial',
                'com_port' => 'COM3',
                'baud_rate' => 9600,
                'is_active' => false,
            ]
        );

        // ── Code mappings ─────────────────────────────────────────────────
        $fbc = LabTest::where('code', 'LAB-FBC')->first();
        $lft = LabTest::where('code', 'LAB-LFT')->first();
        $rft = LabTest::where('code', 'LAB-RFT')->first();
        $lip = LabTest::where('code', 'LAB-LIP')->first();
        $fbs = LabTest::where('code', 'LAB-FBS')->first();
        $uri = LabTest::where('code', 'LAB-URI')->first();

        $mappings = [];

        if ($fbc) {
            foreach (['WBC', 'RBC', 'HGB', 'HCT', 'PLT', 'NEU', 'LYM'] as $code) {
                $mappings[] = [$sysmex->id, $code, $fbc->id];
            }
        }
        if ($lft) {
            foreach (['ALT', 'AST', 'ALP', 'TBIL', 'DBIL', 'TP', 'ALB'] as $code) {
                $mappings[] = [$mindray->id, $code, $lft->id];
            }
        }
        if ($rft) {
            foreach (['UREA', 'CREA', 'NA', 'K', 'CL'] as $code) {
                $mappings[] = [$mindray->id, $code, $rft->id];
            }
        }
        if ($lip) {
            foreach (['CHOL', 'HDL', 'LDL', 'TRIG'] as $code) {
                $mappings[] = [$mindray->id, $code, $lip->id];
            }
        }
        if ($fbs) {
            $mappings[] = [$mindray->id, 'GLU', $fbs->id];
        }
        if ($uri) {
            foreach (['PH', 'SG', 'PRO', 'GLU-U', 'KET', 'BLD', 'LEU', 'NIT'] as $code) {
                $mappings[] = [$cobas->id, $code, $uri->id];
            }
        }

        foreach ($mappings as [$analyzerId, $testCode, $labTestId]) {
            AnalyzerTestMapping::updateOrCreate(
                ['analyzer_id' => $analyzerId, 'analyzer_test_code' => $testCode],
                ['lab_test_id' => $labTestId, 'unit_conversion_factor' => 1.0]
            );
        }
    }
}

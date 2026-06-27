<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualInvestigationSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        $this->seedServices();
        $this->seedRequests();
    }

    private function seedServices(): void
    {
        if (! $this->hasTable('service_catalog')) {
            return;
        }

        $labDept = DB::table('departments')->where('name', 'Main Laboratory')->value('id');
        $tests = [
            ['Haemoglobin Manual', 'numeric', 'g/dL', 12, 17],
            ['Blood Sugar Manual', 'numeric', 'mmol/L', 3.5, 7.8],
            ['Creatinine Manual', 'numeric', 'umol/L', 60, 110],
            ['Malaria Parasite Manual', 'positive_negative', null, null, null],
            ['Pregnancy Test Manual', 'boolean', null, null, null],
            ['Blood Film Comment Manual', 'free_text', null, null, null],
        ];

        foreach ($tests as $i => [$name, $type, $unit, $min, $max]) {
            $this->updateOrInsert('service_catalog', ['code' => $this->ref('LABSVC', $i + 1, 3)], [
                'name' => $name,
                'code' => $this->ref('LABSVC', $i + 1, 3),
                'description' => 'Manual investigation service with '.$type.' overall result.',
                'category' => 'lab',
                'price' => 30 + ($i * 10),
                'department_id' => $labDept,
                'department_type' => 'investigation',
                'is_active' => true,
                'is_billable' => true,
                'overall_result_type' => $type,
                'overall_result_unit' => $unit,
                'overall_result_min_value' => $min,
                'overall_result_max_value' => $max,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    private function seedRequests(): void
    {
        if (! $this->hasTable('lab_requests')) {
            return;
        }

        $existing = $this->countManual('lab_requests', 'request_number');
        $target = $this->target('lab_requests');
        if ($existing >= $target) {
            return;
        }

        $visits = DB::table('visits')->where('visit_number', 'like', 'MT-VIS-%')->select('id', 'patient_id', 'created_by', 'created_at')->limit($target)->get()->values();
        $departmentId = DB::table('departments')->where('name', 'Main Laboratory')->value('id');
        $services = DB::table('service_catalog')->where('code', 'like', 'MT-LABSVC-%')->pluck('id')->values();
        if ($visits->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'sample_collected', 'processing', 'completed', 'validated', 'rejected', 'cancelled'];
        $requests = [];
        for ($i = $existing + 1; $i <= $target; $i++) {
            $visit = $visits[($i - 1) % $visits->count()];
            $requests[] = [
                'request_number' => $this->ref('INV', $i),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'requested_by' => $visit->created_by,
                'department_id' => $departmentId,
                'target_department_id' => $departmentId,
                'clinical_info' => 'Manual investigation scenario '.$i,
                'urgency' => $i % 11 === 0 ? 'urgent' : 'routine',
                'is_emergency' => $i % 17 === 0,
                'status' => $statuses[$i % count($statuses)],
                'created_at' => $visit->created_at,
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('lab_requests', $requests);

        if (! $this->hasTable('lab_request_items')) {
            return;
        }

        $items = [];
        $results = [];
        $labRequests = DB::table('lab_requests')->where('request_number', 'like', 'MT-INV-%')->select('id', 'status', 'requested_by')->get();
        foreach ($labRequests as $index => $request) {
            $serviceId = $services->isNotEmpty() ? $services[$index % $services->count()] : null;
            $items[] = [
                'lab_request_id' => $request->id,
                'service_id' => $serviceId,
                'name' => $this->ref('INVITEM', $index + 1).' Manual investigation item',
                'status' => in_array($request->status, ['completed', 'validated'], true) ? 'completed' : $request->status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        if ($this->countManual('lab_request_items', 'name') === 0) {
            $this->insert('lab_request_items', $items);
        }

        if ($this->hasTable('lab_results') && $this->countManual('lab_results', 'remarks') === 0) {
            $requestItems = DB::table('lab_request_items')
                ->join('lab_requests', 'lab_requests.id', '=', 'lab_request_items.lab_request_id')
                ->where('lab_requests.request_number', 'like', 'MT-INV-%')
                ->whereIn('lab_requests.status', ['completed', 'validated'])
                ->select('lab_request_items.id', 'lab_request_items.lab_request_id', 'lab_requests.requested_by')
                ->get();
            foreach ($requestItems as $index => $item) {
                $results[] = [
                    'lab_request_item_id' => $item->id,
                    'lab_request_id' => $item->lab_request_id,
                    'result_value' => $index % 5 === 0 ? 'Positive' : (string) (10 + ($index % 8)),
                    'is_abnormal' => $index % 6 === 0,
                    'remarks' => $this->ref('LABRES', $index + 1).' Manual result',
                    'performed_by' => $item->requested_by,
                    'verified_by' => $index % 3 === 0 ? $item->requested_by : null,
                    'performed_at' => now()->subHours($index % 72),
                    'verified_at' => $index % 3 === 0 ? now()->subHours($index % 24) : null,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ];
            }
            $this->insert('lab_results', $results);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog;
use App\Services\Maternity\MaternityBillingReadinessService;
use Illuminate\Http\Request;

class MaternityBillingReadinessController extends Controller
{
    public function __construct(private MaternityBillingReadinessService $readiness) {}

    public function show()
    {
        return view('maternity.reports.billing-readiness', [
            'rows' => $this->readiness->rows(),
            'services' => ServiceCatalog::query()->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*.service_id' => ['nullable', 'exists:service_catalog,id'],
            'mappings.*.is_active' => ['nullable', 'boolean'],
            'mappings.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->readiness->save($data, $request->user());

        return redirect()
            ->route('admin.maternity.billing-readiness.show')
            ->with('success', __('maternity.billing_readiness_saved'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ServiceRendering;
use App\Services\ServiceRenderingReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceRenderingReportController extends Controller
{
    public function __construct(private ServiceRenderingReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request, [
            'status',
            'department_id',
            'date_range',
            'date_from',
            'date_to',
        ]);

        return view('service-renderings.reports', [
            'filters' => $filters,
            'summary' => $this->reports->summary($filters, $request->user()),
            'byDepartment' => $this->reports->byDepartment($filters, $request->user()),
            'byStaff' => $this->reports->byStaff($filters, $request->user()),
            'statuses' => ServiceRendering::statuses(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function filters(Request $request, array $keys): array
    {
        $filters = $request->only($keys);
        $dateRange = trim((string) ($filters['date_range'] ?? ''));

        if ($dateRange !== '') {
            $parts = preg_split('/\s+(?:to|-)\s+/', $dateRange);
            $filters['date_from'] = $this->normalizeDate($parts[0] ?? null);
            $filters['date_to'] = $this->normalizeDate($parts[1] ?? ($parts[0] ?? null));
        } else {
            $filters['date_from'] = $this->normalizeDate($filters['date_from'] ?? null);
            $filters['date_to'] = $this->normalizeDate($filters['date_to'] ?? null);
        }

        if (! empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_to'] = $filters['date_from'];
        }

        if (! empty($filters['date_to']) && empty($filters['date_from'])) {
            $filters['date_from'] = $filters['date_to'];
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            if (Carbon::parse($filters['date_from'])->gt(Carbon::parse($filters['date_to']))) {
                [$filters['date_from'], $filters['date_to']] = [$filters['date_to'], $filters['date_from']];
            }

            $filters['date_range'] = $filters['date_from'].' to '.$filters['date_to'];
        }

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    private function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}

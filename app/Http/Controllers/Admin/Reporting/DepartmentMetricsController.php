<?php

namespace App\Http\Controllers\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DepartmentMetricsRegistry;
use Illuminate\Http\Request;

/**
 * Department-TYPE metrics rollup (JSON). Feeds reporting widgets / AJAX with
 * per-department-type counts and revenue. Gated by the reports module +
 * reports.view permission via its route group.
 */
class DepartmentMetricsController extends Controller
{
    public function __construct(
        private DepartmentMetricsRegistry $registry,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return response()->json($this->registry->summary($filters));
    }
}

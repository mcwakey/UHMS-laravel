<?php

namespace App\Http\Controllers\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Services\ReportRegistryService;
use Illuminate\Http\Request;

class ReportsHubController extends Controller
{
    public function __construct(private ReportRegistryService $registry) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return view('reports.index', [
            'sections'    => $this->registry->groupedForUser($user),
            'sectionMeta' => $this->registry->sectionMeta(),
        ]);
    }
}

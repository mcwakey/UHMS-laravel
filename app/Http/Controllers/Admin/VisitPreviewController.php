<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\VisitPreviewService;
use Illuminate\Support\Facades\Gate;

class VisitPreviewController extends Controller
{
    public function show(Visit $visit, VisitPreviewService $service)
    {
        Gate::authorize('visits.preview');

        $preview = $service->build($visit);

        return view('visits.preview', [
            'visit'   => $visit,
            'preview' => $preview,
        ]);
    }
}

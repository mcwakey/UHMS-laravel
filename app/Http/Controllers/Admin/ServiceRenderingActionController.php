<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRendering;
use App\Services\ServiceRenderingService;
use Illuminate\Http\Request;

class ServiceRenderingActionController extends Controller
{
    public function __construct(private ServiceRenderingService $service) {}

    public function start(Request $request, ServiceRendering $serviceRendering)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->start($serviceRendering, $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Service rendering started.');
    }

    public function markRendered(Request $request, ServiceRendering $serviceRendering)
    {
        $data = $request->validate([
            'rendered_at' => ['nullable', 'date'],
            'result_summary' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->service->markRendered($serviceRendering, $request->user(), $data);

        return back()->with('success', 'Service marked as rendered.');
    }

    public function markNotRendered(Request $request, ServiceRendering $serviceRendering)
    {
        $data = $request->validate([
            'reason_not_rendered' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->service->markNotRendered($serviceRendering, $request->user(), $data);

        return back()->with('success', 'Service marked as not rendered.');
    }

    public function cancel(Request $request, ServiceRendering $serviceRendering)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->service->cancel($serviceRendering, $request->user(), $data);

        return back()->with('success', 'Service rendering cancelled.');
    }

    public function updateNotes(Request $request, ServiceRendering $serviceRendering)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'result_summary' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->service->updateNotes($serviceRendering, $request->user(), $data);

        return back()->with('success', 'Rendering notes updated.');
    }
}

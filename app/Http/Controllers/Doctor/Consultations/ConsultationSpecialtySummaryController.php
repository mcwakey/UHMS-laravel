<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternitySummaryPresentationService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtySummaryBuilder;
use Illuminate\Http\Request;

class ConsultationSpecialtySummaryController extends ConsultationWorkflowController
{
    public function preview(
        Request $request,
        Visit $visit,
        ConsultationSpecialtyProfileResolver $resolver,
        ConsultationSpecialtySummaryBuilder $builder,
    ) {
        $routeId = $request->integer('consultation_route_id');
        $route = $routeId
            ? VisitConsultationRoute::query()->with(['department', 'medicalRecord'])->findOrFail($routeId)
            : ($visit->activeConsultationRoute()->with(['department', 'medicalRecord'])->first()
                ?? $visit->consultationRoutes()->with(['department', 'medicalRecord'])->latest('activated_at')->latest('id')->first());

        if (! $route || (int) $route->visit_id !== (int) $visit->id) {
            abort(404);
        }

        $resolved = $resolver->resolve(
            $request->user(),
            visit: $visit,
            consultationRoute: $route,
            department: $route->department ?? $visit->currentDepartment,
        );

        $summary = $builder->build($route, $resolved);

        // Phase 14R.6.1 — the preview follows exactly the same live/snapshot
        // rule as the main summary, from the SAME presentation service. There
        // is no preview-specific maternity logic.
        $maternity = app(ConsultationMaternitySummaryPresentationService::class)->build(
            $route,
            $request->user(),
            isGynaecology: $resolved->profile?->code === 'gynecology',
        );

        return response()->json([
            'success' => true,
            'summary' => $summary->toArray(),
            'maternity_context' => $maternity->shouldRender()
                ? $maternity->toArray() + [
                    'html' => view('consultations.partials.maternity.summary-'
                        .($maternity->isLive() ? 'live' : 'snapshot'), [
                            'maternitySummary' => $maternity,
                        ])->render(),
                ]
                : null,
        ]);
    }
}

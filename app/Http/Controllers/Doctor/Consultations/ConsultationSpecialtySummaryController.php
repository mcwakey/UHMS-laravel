<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Models\Visit;
use App\Models\VisitConsultationRoute;
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

        return response()->json([
            'success' => true,
            'summary' => $summary->toArray(),
        ]);
    }
}

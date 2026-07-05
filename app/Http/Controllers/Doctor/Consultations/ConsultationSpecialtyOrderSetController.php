<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\Visit;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\Specialty\ConsultationSpecialtyOrderSetService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConsultationSpecialtyOrderSetController extends ConsultationWorkflowController
{
    public function index(
        Request $request,
        Visit $visit,
        ConsultationSpecialtyOrderSetService $orderSets,
        ConsultationSpecialtyProfileResolver $resolver,
    ) {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'specialty_order_set.index', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $resolved = $resolver->resolve($request->user(), visit: $visit, consultationRoute: $context->route, department: $context->route?->department);

        return response()->json([
            'success' => true,
            'order_sets' => $orderSets->getWorkspaceOrderSets($resolved),
        ]);
    }

    public function preview(
        Request $request,
        Visit $visit,
        ConsultationSpecialtyOrderSet $orderSet,
        ConsultationSpecialtyOrderSetService $orderSets,
        ConsultationSpecialtyProfileResolver $resolver,
    ) {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'specialty_order_set.preview', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $resolved = $resolver->resolve($request->user(), visit: $visit, consultationRoute: $context->route, department: $context->route?->department);
        $this->assertOrderSetMatchesProfile($orderSet, $resolved->profile->id);

        return response()->json([
            'success' => true,
            'preview' => $orderSets->previewOrderSet($context->route, $orderSet, $request->user()),
        ]);
    }

    public function apply(
        Request $request,
        Visit $visit,
        ConsultationSpecialtyOrderSet $orderSet,
        ConsultationSpecialtyOrderSetService $orderSets,
        ConsultationSpecialtyProfileResolver $resolver,
    ) {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'specialty_order_set.apply', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $resolved = $resolver->resolve($request->user(), visit: $visit, consultationRoute: $context->route, department: $context->route?->department);
        $this->assertOrderSetMatchesProfile($orderSet, $resolved->profile->id);

        $data = $request->validate([
            'selected_item_ids' => ['nullable', 'array'],
            'selected_item_ids.*' => ['integer', 'exists:consultation_specialty_order_set_items,id'],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $application = $orderSets->applyOrderSet(
            $context->route,
            $orderSet,
            $request->user(),
            $data['selected_item_ids'] ?? [],
            ['overwrite' => $request->boolean('overwrite')],
        );

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('consultation_specialties.order_sets.'.$application->status),
                'application' => $application->fresh(['items']),
            ]);
        }

        return back()
            ->withFragment('order-sets-panel')
            ->with('success', __('consultation_specialties.order_sets.'.$application->status));
    }

    private function assertOrderSetMatchesProfile(ConsultationSpecialtyOrderSet $orderSet, int $profileId): void
    {
        if (! $orderSet->is_active) {
            throw ValidationException::withMessages([
                'order_set' => __('consultation_specialties.order_sets.inactive_warning'),
            ]);
        }

        if ((int) $orderSet->consultation_specialty_profile_id !== $profileId) {
            throw ValidationException::withMessages([
                'order_set' => __('consultation_specialties.order_sets.wrong_profile'),
            ]);
        }
    }
}

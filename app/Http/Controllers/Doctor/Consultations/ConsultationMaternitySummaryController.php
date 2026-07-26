<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Models\Visit;
use App\Services\Consultation\Maternity\ConsultationMaternitySummaryPresentationService;
use App\Services\ConsultationSessionService;
use Illuminate\Http\Request;

/**
 * Phase 14R.6.1 — READ-ONLY maternity summary surfaces.
 *
 * Both endpoints are **GET**. There is deliberately no store, update or destroy
 * action here and none anywhere else: a snapshot cannot be edited, deleted,
 * re-hashed or fabricated through the UI.
 *
 * Every snapshot lookup is scoped to the consultation resolved from the route
 * model, so a version belonging to another consultation can never be read
 * through this controller.
 */
class ConsultationMaternitySummaryController extends ConsultationWorkflowController
{
    /**
     * Render a selected completion-snapshot version.
     *
     * Selecting a version performs no write and does not verify the payload
     * against live maternity data — only against its own captured hash.
     */
    public function history(
        Request $request,
        Visit $visit,
        ConsultationMaternitySummaryPresentationService $presentation,
    ) {
        [$route, $user] = $this->context($request, $visit, $presentation);

        $version = $request->integer('version') ?: null;

        $viewModel = $presentation->build($route, $user, version: $version);

        // A version that does not belong to this consultation resolves to no
        // snapshot rather than to someone else's data.
        abort_if(! $viewModel->shouldRender(), 403);

        if ($version !== null && $viewModel->isMissingSnapshot()) {
            abort(404);
        }

        return view('consultations.partials.maternity.summary-snapshot', [
            'maternitySummary' => $viewModel,
        ]);
    }

    /**
     * The CURRENT live maternity record, loaded on demand from a completed
     * consultation.
     *
     * Requires the bridge permission and the underlying maternity permission on
     * top of summary access, so the snapshot UI never becomes a back door into
     * records the user could not otherwise view.
     */
    public function currentRecord(
        Request $request,
        Visit $visit,
        ConsultationMaternitySummaryPresentationService $presentation,
    ) {
        [$route, $user] = $this->context($request, $visit, $presentation);

        abort_unless($presentation->mayViewCurrentRecord($user), 403);

        $payload = $presentation->currentRecord($route, $user);

        return view('consultations.partials.maternity.current-record-body', [
            'payload' => $payload ?? [],
            'available' => $payload !== null,
        ]);
    }

    /**
     * Resolve the consultation route and assert summary access.
     *
     * @return array{0: \App\Models\VisitConsultationRoute, 1: \App\Models\User}
     */
    private function context(
        Request $request,
        Visit $visit,
        ConsultationMaternitySummaryPresentationService $presentation,
    ): array {
        abort_unless($presentation->enabled(), 403);

        $user = $request->user();
        abort_unless($user?->can('consultation.maternity_context.summary.view'), 403);

        $route = app(ConsultationSessionService::class)
            ->resolveRouteForVisit($visit, $request->integer('consultation_route_id') ?: null);

        abort_if(! $route, 404);
        abort_if((int) $route->visit_id !== (int) $visit->id, 404);

        return [$route, $user];
    }
}

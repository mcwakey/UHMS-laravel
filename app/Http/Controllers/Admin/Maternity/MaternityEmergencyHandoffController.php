<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\PostnatalCase;
use App\Services\Maternity\Context\MaternityLinkException;
use App\Services\Maternity\Handoffs\MaternityEmergencyHandoffService;
use App\Support\Maternity\MaternityReturnContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Phase 14R.5 — explicit "Create/Open Emergency Case" from Maternity.
 *
 * An escalation FLAG never creates an emergency case; only this explicit
 * clinician action does, and only through the existing EmergencyCaseService.
 *
 * Repeated clicks reuse the existing active linked case. No Admission Request
 * and no Theatre case is created here.
 */
class MaternityEmergencyHandoffController extends Controller
{
    public function __construct(
        private readonly MaternityEmergencyHandoffService $handoffs,
    ) {}

    public function fromLabor(Request $request, LaborEpisode $laborEpisode)
    {
        return $this->escalate($request, $laborEpisode, 'admin.maternity.labor.show', ['laborEpisode' => $laborEpisode->id]);
    }

    public function fromDelivery(Request $request, DeliveryRecord $deliveryRecord)
    {
        return $this->escalate($request, $deliveryRecord, 'admin.maternity.deliveries.show', ['deliveryRecord' => $deliveryRecord->id]);
    }

    public function fromPostnatal(Request $request, PostnatalCase $postnatalCase)
    {
        return $this->escalate($request, $postnatalCase, 'admin.maternity.postnatal.show', ['postnatalCase' => $postnatalCase->id]);
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    private function escalate(Request $request, Model $source, string $fallbackRoute, array $fallbackParams)
    {
        abort_unless(
            $this->handoffs->enabled(),
            403,
            __('maternity_handoffs.messages.handoff_unavailable')
        );

        // Bridge permission + the existing Emergency case-create permission.
        abort_unless($request->user()?->can('maternity.emergency_handoff.create'), 403);
        abort_unless(
            $request->user()?->can('emergency.case.create'),
            403,
            __('consultation_maternity.messages.maternity_permission_required')
        );

        $data = $request->validate([
            'arrival_mode' => ['nullable', 'string', 'in:WALK_IN,AMBULANCE,POLICE,FAMILY_BROUGHT,REFERRAL,TRANSFER_FROM_OPD,TRANSFER_FROM_WARD,UNKNOWN'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'initial_condition' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->handoffs->createOrReuseEmergencyCase($source, $data, $request->user());
        } catch (MaternityLinkException $e) {
            return $this->respond($request, false, $e->getMessage(), $fallbackRoute, $fallbackParams);
        }

        $message = $result['reused']
            ? __('maternity_handoffs.messages.emergency_case_exists', ['id' => $result['case']->id])
            : __('maternity_handoffs.messages.emergency_case_created', ['id' => $result['case']->id]);

        return $this->respond($request, true, $message, $fallbackRoute, $fallbackParams);
    }

    /**
     * Preserve the originating Maternity return context, validated as an
     * internal named route; otherwise fall back to the source record's own page.
     */
    private function respond(Request $request, bool $ok, string $message, string $fallbackRoute, array $fallbackParams)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $message], $ok ? 200 : 422);
        }

        $target = MaternityReturnContext::fromArray($request->all())?->url()
            ?? route($fallbackRoute, $fallbackParams);

        return redirect()->to($target)->with($ok ? 'success' : 'error', $message);
    }
}

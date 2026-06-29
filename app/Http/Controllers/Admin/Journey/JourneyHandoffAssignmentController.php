<?php

namespace App\Http\Controllers\Admin\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Http\Controllers\Controller;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;
use App\Services\Journey\JourneyEscalationService;
use App\Services\Journey\JourneyHandoffAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use RuntimeException;

/**
 * Operational coordination actions for cross-department handoffs: claim / assign /
 * acknowledge / resolve. Authorization is enforced in the service (capability over
 * the destination domain); failures fall back gracefully with a flash message.
 */
class JourneyHandoffAssignmentController extends Controller
{
    public function __construct(
        private JourneyHandoffAssignmentService $assignments,
        private JourneyEscalationService $escalation,
    ) {}

    public function claim(Request $request)
    {
        $user = $request->user();
        $handoff = $this->resolveHandoff($request, $user);
        if ($handoff === null) {
            return back()->with('error', __('journey.handoff.no_longer_active'));
        }

        return $this->run(fn () => $this->escalation->applyEscalation($handoff, $this->assignments->claim($handoff, $user), $user));
    }

    public function assign(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'visit_id' => 'required|integer',
            'cause' => 'nullable|string',
            'assignee_id' => 'required|integer|exists:users,id',
        ]);

        $handoff = $this->resolveHandoff($request, $user);
        if ($handoff === null) {
            return back()->with('error', __('journey.handoff.no_longer_active'));
        }
        $assignee = User::findOrFail($data['assignee_id']);

        return $this->run(fn () => $this->escalation->applyEscalation($handoff, $this->assignments->assignTo($handoff, $assignee, $user), $user));
    }

    public function acknowledge(Request $request, JourneyHandoffAssignment $assignment)
    {
        return $this->run(fn () => $this->assignments->acknowledge($assignment, $request->user()));
    }

    public function resolve(Request $request, JourneyHandoffAssignment $assignment)
    {
        $note = $request->validate(['note' => 'nullable|string|max:500'])['note'] ?? null;

        return $this->run(
            fn () => $this->assignments->resolve($assignment, $request->user(), $note),
            __('journey.handoff.resolved'),
        );
    }

    /** Run a coordination action, translating service failures into safe flashes/403s. */
    private function run(callable $action, ?string $success = null)
    {
        try {
            $action();
        } catch (UnauthorizedException) {
            abort(403);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success ?? __('journey.handoff.assignment_updated'));
    }

    /** Re-derive the current handoff a coordination action targets; null if no longer active. */
    private function resolveHandoff(Request $request, ?User $user): ?JourneyHandoff
    {
        $visitId = (int) $request->input('visit_id');
        if ($visitId === 0) {
            return null;
        }

        $handoff = $this->assignments->activeHandoffFor($visitId, $user);
        if ($handoff === null) {
            return null;
        }

        // The posted cause must still match the derived handoff (it may have moved on).
        $cause = $request->input('cause');
        if ($cause && $handoff->cause->value !== $cause) {
            return null;
        }

        return $handoff;
    }
}

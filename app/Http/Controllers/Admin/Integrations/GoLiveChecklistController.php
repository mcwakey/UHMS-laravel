<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\IntegrationProvider;
use App\Models\IntegrationProviderChecklist;
use App\Models\IntegrationProviderChecklistItem;
use App\Services\Integrations\ProviderGoLiveChecklistService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoLiveChecklistController extends Controller
{
    private const VIEW_PERMS = ['integrations.payments.golive.view', 'integrations.sms.golive.view'];

    public function __construct(protected ProviderGoLiveChecklistService $checklists) {}

    public function index(Request $request)
    {
        abort_unless($request->user()?->canAny(self::VIEW_PERMS), 403);

        $providers = IntegrationProvider::query()
            ->orderBy('module_type')->orderByDesc('is_active')->orderBy('name')
            ->get();
        $checklists = IntegrationProviderChecklist::pluck('live_ready', 'integration_provider_id');
        $statuses = IntegrationProviderChecklist::pluck('status', 'integration_provider_id');

        return view('admin.integrations.golive.index', compact('providers', 'checklists', 'statuses'));
    }

    public function show(Request $request, IntegrationProvider $provider)
    {
        abort_unless($request->user()?->canAny(self::VIEW_PERMS), 403);

        $checklist = $this->checklists->forProvider($provider);
        $itemStatuses = [
            IntegrationProviderChecklistItem::STATUS_PENDING,
            IntegrationProviderChecklistItem::STATUS_PASSED,
            IntegrationProviderChecklistItem::STATUS_FAILED,
            IntegrationProviderChecklistItem::STATUS_NOT_APPLICABLE,
            IntegrationProviderChecklistItem::STATUS_WAIVED,
        ];

        return view('admin.integrations.golive.show', compact('provider', 'checklist', 'itemStatuses'));
    }

    public function updateItem(Request $request, IntegrationProviderChecklist $checklist)
    {
        $data = $request->validate([
            'item_key' => ['required', 'string'],
            'status' => ['required', Rule::in(['pending', 'passed', 'failed', 'not_applicable', 'waived'])],
            'evidence_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'waiver_reason' => ['nullable', 'string', 'max:255', 'required_if:status,waived'],
        ]);

        $this->checklists->updateItem($checklist, $data['item_key'], $data);

        return back()->with('success', __('integrations.flash.golive_item_updated'));
    }

    public function signoff(Request $request, IntegrationProviderChecklist $checklist)
    {
        $data = $request->validate(['type' => ['required', Rule::in(['finance', 'it'])]]);
        $this->checklists->recordSignoff($checklist, $data['type']);

        return back()->with('success', __('integrations.flash.golive_signoff_recorded'));
    }

    public function approve(IntegrationProviderChecklist $checklist)
    {
        $checklist = $this->checklists->approve($checklist);

        return back()->with(
            $checklist->live_ready ? 'success' : 'error',
            $checklist->live_ready ? __('integrations.flash.golive_approved') : __('integrations.errors.not_live_ready'),
        );
    }
}

<?php

namespace App\Services\Integrations;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\IntegrationProvider;
use App\Models\IntegrationProviderChecklist;
use App\Models\IntegrationProviderChecklistItem;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

/**
 * Manages the provider go-live checklist: default items, item status + evidence,
 * sign-offs, approval and live-readiness. Evidence never stores secrets.
 */
class ProviderGoLiveChecklistService
{
    /** Required technical checks (sign-offs/approval are tracked on the checklist row). */
    public const DEFAULT_ITEMS = [
        'sandbox_credentials_configured',
        'test_connection_passed',
        'test_message_sent',
        'callback_url_configured',
        'callback_signature_confirmed',
        'sandbox_callback_received',
        'amount_currency_validation_tested',
        'duplicate_callback_tested',
        'failed_transaction_tested',
        'live_credentials_configured',
        'live_callback_url_configured',
        'live_provider_status_verified',
    ];

    public function __construct(protected ActivityLogService $logger) {}

    public function forProvider(IntegrationProvider $provider): IntegrationProviderChecklist
    {
        $checklist = IntegrationProviderChecklist::firstOrNew(['integration_provider_id' => $provider->id]);
        if (! $checklist->exists) {
            $checklist->status = IntegrationProviderChecklist::STATUS_PENDING;
            $checklist->save();
            foreach (self::DEFAULT_ITEMS as $key) {
                $checklist->items()->create(['item_key' => $key, 'status' => 'pending', 'is_required' => true]);
            }
            $this->logger->log(LogModule::INTEGRATIONS, 'PROVIDER_GOLIVE_CHECKLIST_CREATED', [
                'source_type' => 'integration_provider', 'source_id' => $provider->id,
            ], $provider, "Go-live checklist created: {$provider->name}");
        }

        return $checklist->load('items');
    }

    public function updateItem(IntegrationProviderChecklist $checklist, string $itemKey, array $data): IntegrationProviderChecklistItem
    {
        $item = $checklist->items()->where('item_key', $itemKey)->firstOrFail();

        $item->update([
            'status' => $data['status'] ?? $item->status,
            'evidence_reference' => $data['evidence_reference'] ?? $item->evidence_reference,
            'notes' => $data['notes'] ?? $item->notes,
            'waiver_reason' => ($data['status'] ?? null) === IntegrationProviderChecklistItem::STATUS_WAIVED
                ? ($data['waiver_reason'] ?? null) : null,
            'recorded_by' => Auth::id(),
            'recorded_at' => now(),
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PROVIDER_GOLIVE_ITEM_UPDATED', [
            'source_type' => 'integration_provider_checklist_item', 'source_id' => $item->id,
            'metadata' => ['item' => $itemKey, 'status' => $item->status],
        ], $item, "Go-live item updated: {$itemKey}");

        $this->recompute($checklist->fresh('items'));

        return $item;
    }

    public function recordSignoff(IntegrationProviderChecklist $checklist, string $type): IntegrationProviderChecklist
    {
        $field = $type === 'finance' ? 'finance_signoff' : 'it_signoff';
        $checklist->update([
            "{$field}_by" => Auth::id(),
            "{$field}_at" => now(),
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PROVIDER_GOLIVE_SIGNOFF_RECORDED', [
            'source_type' => 'integration_provider_checklist', 'source_id' => $checklist->id,
            'metadata' => ['type' => $type],
        ], $checklist, "Go-live {$type} sign-off recorded");

        return $this->recompute($checklist->fresh('items'));
    }

    public function approve(IntegrationProviderChecklist $checklist): IntegrationProviderChecklist
    {
        $this->recompute($checklist);
        if (! $checklist->fresh()->live_ready) {
            return $checklist;
        }

        $checklist->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'status' => IntegrationProviderChecklist::STATUS_READY,
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PROVIDER_GOLIVE_SIGNOFF_RECORDED', [
            'source_type' => 'integration_provider_checklist', 'source_id' => $checklist->id,
            'metadata' => ['type' => 'approval'],
            'severity' => LogSeverity::WARNING,
        ], $checklist, 'Go-live approved');

        return $checklist->refresh();
    }

    /** A checklist is live-ready when every required item is satisfied and both sign-offs exist. */
    public function isLiveReady(IntegrationProviderChecklist $checklist): bool
    {
        $checklist->loadMissing('items');
        $itemsOk = $checklist->items->every(fn ($i) => $i->isSatisfied());
        return $itemsOk && $checklist->finance_signoff_at !== null && $checklist->it_signoff_at !== null;
    }

    public function recompute(IntegrationProviderChecklist $checklist): IntegrationProviderChecklist
    {
        $ready = $this->isLiveReady($checklist);
        $hasFailed = $checklist->items->contains(fn ($i) => $i->is_required && $i->status === IntegrationProviderChecklistItem::STATUS_FAILED);

        $checklist->update([
            'live_ready' => $ready,
            'status' => $ready
                ? IntegrationProviderChecklist::STATUS_READY
                : ($hasFailed ? IntegrationProviderChecklist::STATUS_BLOCKED : IntegrationProviderChecklist::STATUS_IN_PROGRESS),
        ]);

        return $checklist->refresh();
    }

    /** Whether a provider may be activated in LIVE mode without an override. */
    public function liveActivationAllowed(IntegrationProvider $provider): bool
    {
        $checklist = IntegrationProviderChecklist::where('integration_provider_id', $provider->id)->first();
        return $checklist?->live_ready === true;
    }
}

<?php

namespace Database\Seeders;

use App\Console\Commands\ObgynOrderSetAuditCommand;
use App\Enums\LogModule;
use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Services\ActivityLogService;
use Illuminate\Database\Seeder;

/**
 * Phase 14R.4 (decision R5) — retarget seeded order-set items that currently
 * patch maternity-owned specialty JSON.
 *
 * Safety properties:
 *   - **Idempotent.** Re-running changes nothing once retargeted.
 *   - **Never overwrites an administrator's edit.** An item is retargeted only
 *     when its payload EXACTLY matches the previously-seeded definition;
 *     anything else is left untouched and reported as needing review.
 *   - **Historical applications are untouched.** Only item *definitions* are
 *     changed; past `consultation_specialty_order_set_applications` and their
 *     items remain exactly as they were recorded.
 *
 * After retargeting, applying the order set presents an explicit clinician
 * action instead of silently writing a specialty entry — it never creates a
 * pregnancy profile or an ANC visit automatically.
 */
class ConsultationSpecialtyOrderSetMaternityReconciliationSeeder extends Seeder
{
    public const APPLY_MODE = 'maternity_context_action';

    public function run(): void
    {
        $retargeted = [];
        $needsReview = [];

        $items = ConsultationSpecialtyOrderSetItem::query()
            ->where('apply_mode', 'patch_specialty_entry')
            ->with('orderSet')
            ->get();

        foreach ($items as $item) {
            $definition = ObgynOrderSetAuditCommand::retargetDefinitionFor($item);

            if (! $definition) {
                // Only report items that actually touch a guarded section.
                $section = $item->payload['section_key'] ?? $item->target_section;
                if (in_array($section, ['current_pregnancy', 'birth_plan'], true)) {
                    $needsReview[] = $item->id;
                }

                continue;
            }

            $item->forceFill([
                'apply_mode' => self::APPLY_MODE,
                'payload' => [
                    'maternity_action' => $definition['action'],
                    // Kept for audit: what this item used to do.
                    'retargeted_from' => [
                        'apply_mode' => 'patch_specialty_entry',
                        'payload' => $item->payload,
                    ],
                ],
            ])->save();

            $retargeted[] = $item->id;
        }

        if ($retargeted !== []) {
            app(ActivityLogService::class)->log(
                LogModule::CONSULTATION,
                'OBGYN_ORDER_SET_MATERNITY_ACTION_RETARGETED',
                ['metadata' => [
                    'retargeted_item_ids' => $retargeted,
                    'needs_review_item_ids' => $needsReview,
                ]],
                null,
                'OBGYN_ORDER_SET_MATERNITY_ACTION_RETARGETED',
            );
        }

        if ($this->command) {
            $this->command->info(sprintf(
                'Order-set maternity reconciliation: %d retargeted, %d needing review.',
                count($retargeted),
                count($needsReview),
            ));
        }
    }
}

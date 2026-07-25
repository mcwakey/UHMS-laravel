<?php

namespace App\Console\Commands;

use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Services\Consultation\Maternity\ConsultationMaternitySpecialtyWriteGuard;
use Illuminate\Console\Command;

/**
 * Phase 14R.4 — READ-ONLY inventory of order-set items that target
 * maternity-owned specialty fields.
 *
 * Makes no writes of any kind. Use it before and after running the retargeting
 * seeder, and to spot administrator-authored order sets that need review.
 */
class ObgynOrderSetAuditCommand extends Command
{
    protected $signature = 'consultation:obgyn-order-set-audit {--json : Emit machine-readable JSON}';

    protected $description = 'Audit order-set items that write maternity-owned specialty fields (read-only)';

    /**
     * The EXACT previously-seeded payloads the reconciliation seeder is allowed
     * to retarget. Order-set items carry no stable code of their own, so they
     * are matched on section + merge payload. Anything that differs — i.e. an
     * administrator edited it — is classified "custom / needs review" and left
     * untouched.
     *
     * @var list<array{section: string, merge: array<string, mixed>, action: string}>
     */
    public const RETARGETABLE_PAYLOADS = [
        [
            'section' => 'current_pregnancy',
            'merge' => ['pregnancy_confirmed' => true],
            'action' => 'create_or_link_pregnancy_profile',
        ],
        [
            'section' => 'birth_plan',
            'merge' => ['danger_signs_counseling' => true],
            'action' => 'record_anc_counselling',
        ],
    ];

    /** Returns the matching retarget definition for an item, or null. */
    public static function retargetDefinitionFor(ConsultationSpecialtyOrderSetItem $item): ?array
    {
        if ($item->apply_mode !== 'patch_specialty_entry') {
            return null;
        }

        $payload = $item->payload ?? [];
        $section = $payload['section_key'] ?? $item->target_section;
        $merge = $payload['merge'] ?? [];

        foreach (self::RETARGETABLE_PAYLOADS as $definition) {
            if ($section === $definition['section'] && $merge == $definition['merge']) {
                return $definition;
            }
        }

        return null;
    }

    public function handle(ConsultationMaternitySpecialtyWriteGuard $guard): int
    {
        // Union of every guarded section/field across both O&G profiles.
        $guarded = collect($guard->matrix())
            ->mergeRecursive($guard->matrixFor(ConsultationMaternitySpecialtyWriteGuard::GYNAECOLOGY_PROFILE_CODE))
            ->map(fn ($fields) => collect($fields)->flatten()->unique()->values()->all());

        $rows = [];

        $items = ConsultationSpecialtyOrderSetItem::query()
            ->with(['orderSet.profile'])
            ->get();

        foreach ($items as $item) {
            $section = $item->target_section ?: ($item->payload['section_key'] ?? null);

            if (! $section) {
                continue;
            }

            $guardedFields = $guarded->get($section, []);

            if ($guardedFields === []) {
                continue;
            }

            // Fields this item would actually write.
            $merge = $item->payload['merge'] ?? [];
            $touched = array_values(array_intersect(
                array_keys(is_array($merge) ? $merge : []),
                $guardedFields
            ));

            if ($item->target_field && in_array($item->target_field, $guardedFields, true)) {
                $touched[] = $item->target_field;
            }

            $touched = array_values(array_unique($touched));

            if ($touched === []) {
                continue;
            }

            $rows[] = [
                'order_set' => $item->orderSet?->code ?? '—',
                'order_set_id' => $item->consultation_specialty_order_set_id,
                'item_id' => $item->id,
                'profile' => $item->orderSet?->profile?->code ?? '—',
                'apply_mode' => $item->apply_mode,
                'section' => $section,
                'fields' => implode(', ', $touched),
                'classification' => $this->classify($item),
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($rows === []) {
            $this->info('No order-set items target maternity-owned specialty fields.');

            return self::SUCCESS;
        }

        $this->table(
            ['Order set', 'Set ID', 'Item', 'Profile', 'Apply mode', 'Section', 'Fields', 'Classification'],
            $rows
        );

        $this->newLine();
        $this->line('This command made no writes.');

        return self::SUCCESS;
    }

    private function classify(ConsultationSpecialtyOrderSetItem $item): string
    {
        if ($item->apply_mode === 'maternity_context_action') {
            return 'already retargeted';
        }

        if ($item->apply_mode !== 'patch_specialty_entry') {
            return 'unsupported';
        }

        return self::retargetDefinitionFor($item)
            ? 'safe to retarget'
            : 'custom / needs review';
    }
}

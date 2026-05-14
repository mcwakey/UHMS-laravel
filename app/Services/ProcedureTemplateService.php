<?php

namespace App\Services;

use App\Models\ProcedureRequest;
use App\Models\ProcedureTemplateValue;
use App\Models\ServiceCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProcedureTemplateService
{
    public function __construct(private ProcedureCatalogueService $catalogue) {}

    /**
     * Save values entered for a given template stage on a procedure request.
     * Required-field validation is enforced for active fields tagged is_required.
     *
     * $values: ['field_key' => 'value', ...]
     */
    public function saveValues(
        ProcedureRequest $request,
        string $templateType,
        array $values,
        ?int $userId = null,
    ): array {
        if (! in_array($templateType, ProcedureCatalogueService::TEMPLATE_TYPES, true)) {
            throw new InvalidArgumentException("Invalid template_type: {$templateType}");
        }
        $service = $request->service;
        if (! $service) {
            // No service configured → nothing to save (just no-op).
            return [];
        }

        $fields = $this->catalogue->fieldsFor($service, $templateType);
        if ($fields->isEmpty()) {
            return [];
        }

        // Required field validation
        $missing = [];
        foreach ($fields as $f) {
            if ($f->is_required) {
                $val = $values[$f->field_key] ?? null;
                if ($val === null || $val === '') {
                    $missing[] = $f->label;
                }
            }
        }
        if (! empty($missing)) {
            throw new InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing),
            );
        }

        $userId ??= Auth::id();
        $saved = [];

        DB::transaction(function () use (&$saved, $request, $service, $templateType, $fields, $values, $userId) {
            foreach ($fields as $f) {
                if (! array_key_exists($f->field_key, $values)) continue;
                $raw = $values[$f->field_key];
                $value = is_array($raw) ? json_encode($raw) : (string) $raw;

                $saved[] = ProcedureTemplateValue::updateOrCreate(
                    [
                        'procedure_request_id' => $request->id,
                        'template_field_id'    => $f->id,
                    ],
                    [
                        'service_id'    => $service->id,
                        'template_type' => $templateType,
                        'field_key'     => $f->field_key,
                        'field_label'   => $f->label,
                        'input_type'    => $f->input_type,
                        'value'         => $value,
                        'recorded_by'   => $userId,
                        'recorded_at'   => now(),
                    ],
                );
            }
        });

        return $saved;
    }

    /**
     * Get values for a template stage in [field_key => value] form.
     */
    public function getValues(ProcedureRequest $request, string $templateType): array
    {
        return ProcedureTemplateValue::where('procedure_request_id', $request->id)
            ->where('template_type', $templateType)
            ->get()
            ->mapWithKeys(fn ($v) => [$v->field_key => $v->value])
            ->toArray();
    }

    /**
     * Used by views to render forms / reports.
     * Returns [ section => [fields], '_ungrouped' => [fields] ]
     */
    public function buildStageView(ServiceCatalog $service, string $templateType, ?ProcedureRequest $request = null): array
    {
        $sections = $this->catalogue->sectionsFor($service, $templateType);
        $fields   = $this->catalogue->fieldsFor($service, $templateType);
        $values   = $request ? $this->getValues($request, $templateType) : [];

        $bySection = [];
        foreach ($sections as $section) {
            $bySection[$section->id] = [
                'section' => $section,
                'fields' => [],
            ];
        }

        $ungrouped = [];
        foreach ($fields as $f) {
            $f->_value = $values[$f->field_key] ?? $f->default_value;
            if ($f->section_id) {
                if (! isset($bySection[$f->section_id])) {
                    $bySection[$f->section_id] = [
                        'section' => (object) [
                            'name' => 'Additional Fields',
                            'description' => null,
                        ],
                        'fields' => [],
                    ];
                }

                $bySection[$f->section_id]['fields'][] = $f;
            } else {
                $ungrouped[] = $f;
            }
        }

        return [
            'sections'  => $sections,
            'by_section' => array_values(array_filter($bySection, fn (array $group): bool => ! empty($group['fields']))),
            'ungrouped' => $ungrouped,
            'values'    => $values,
        ];
    }
}

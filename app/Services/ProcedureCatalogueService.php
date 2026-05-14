<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\ProcedureTemplateField;
use App\Models\ProcedureTemplateSection;
use App\Models\ServiceCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProcedureCatalogueService
{
    public const TPL_PRE_OP         = 'PRE_OP';
    public const TPL_ANAESTHESIA    = 'ANAESTHESIA';
    public const TPL_OPERATIVE_NOTE = 'OPERATIVE_NOTE';
    public const TPL_POST_OP        = 'POST_OP';
    public const TPL_FULL_REPORT    = 'FULL_REPORT';

    public const TEMPLATE_TYPES = [
        'PRE_OP', 'ANAESTHESIA', 'OPERATIVE_NOTE', 'POST_OP', 'FULL_REPORT',
    ];

    public const INPUT_TYPES = [
        'text', 'textarea', 'number', 'select', 'checkbox', 'date', 'time', 'datetime', 'file',
    ];

    /**
     * List services that belong to procedure-type departments.
     */
    public function listProcedureServices(?string $search = null)
    {
        return ServiceCatalog::query()
            ->with('department')
            ->whereHas('department', fn ($q) => $q->where('type', DepartmentType::PROCEDURE->value))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();
    }

    public function ensureProcedureService(ServiceCatalog $service): void
    {
        $type = $service->department?->type;
        $value = $type instanceof DepartmentType ? $type->value : (string) $type;
        if ($value !== DepartmentType::PROCEDURE->value) {
            throw new InvalidArgumentException('Service is not a procedure-type service.');
        }
    }

    /**
     * Section CRUD
     */
    public function addSection(ServiceCatalog $service, array $data): ProcedureTemplateSection
    {
        $this->ensureProcedureService($service);
        $this->assertValidTemplateType($data['template_type'] ?? '');
        return ProcedureTemplateSection::create([
            'service_id'    => $service->id,
            'template_type' => $data['template_type'],
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'is_active'     => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function updateSection(ProcedureTemplateSection $section, array $data): ProcedureTemplateSection
    {
        if (isset($data['template_type'])) $this->assertValidTemplateType($data['template_type']);
        $section->fill(array_intersect_key($data, array_flip([
            'template_type', 'name', 'description', 'sort_order', 'is_active',
        ])))->save();
        return $section;
    }

    public function deleteSection(ProcedureTemplateSection $section): void
    {
        $section->delete();
    }

    /**
     * Field CRUD
     */
    public function addField(ServiceCatalog $service, array $data): ProcedureTemplateField
    {
        $this->ensureProcedureService($service);
        $this->assertValidTemplateType($data['template_type'] ?? '');
        $this->assertValidInputType($data['input_type'] ?? 'text');

        return ProcedureTemplateField::create([
            'service_id'    => $service->id,
            'section_id'    => $data['section_id'] ?? null,
            'template_type' => $data['template_type'],
            'label'         => $data['label'],
            'field_key'     => $data['field_key'] ?? str($data['label'])->slug('_')->toString(),
            'input_type'    => $data['input_type'] ?? 'text',
            'options'       => $data['options'] ?? null,
            'default_value' => $data['default_value'] ?? null,
            'is_required'   => (bool) ($data['is_required'] ?? false),
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'is_active'     => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function updateField(ProcedureTemplateField $field, array $data): ProcedureTemplateField
    {
        if (isset($data['template_type'])) $this->assertValidTemplateType($data['template_type']);
        if (isset($data['input_type']))    $this->assertValidInputType($data['input_type']);

        $field->fill(array_intersect_key($data, array_flip([
            'section_id', 'template_type', 'label', 'field_key', 'input_type',
            'options', 'default_value', 'is_required', 'sort_order', 'is_active',
        ])))->save();
        return $field;
    }

    public function deleteField(ProcedureTemplateField $field): void
    {
        $field->delete();
    }

    /**
     * Helpers used by templates.
     */
    public function sectionsFor(ServiceCatalog $service, string $templateType)
    {
        return ProcedureTemplateSection::where('service_id', $service->id)
            ->where('template_type', $templateType)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    public function fieldsFor(ServiceCatalog $service, string $templateType)
    {
        return ProcedureTemplateField::where('service_id', $service->id)
            ->where('template_type', $templateType)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    /* ---------------- internal ---------------- */

    protected function assertValidTemplateType(string $type): void
    {
        if (! in_array($type, self::TEMPLATE_TYPES, true)) {
            throw new InvalidArgumentException("Invalid template_type: {$type}");
        }
    }

    protected function assertValidInputType(string $type): void
    {
        if (! in_array($type, self::INPUT_TYPES, true)) {
            throw new InvalidArgumentException("Invalid input_type: {$type}");
        }
    }
}

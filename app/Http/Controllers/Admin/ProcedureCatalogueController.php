<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcedureTemplateField;
use App\Models\ProcedureTemplateSection;
use App\Models\ServiceCatalog;
use App\Services\ProcedureCatalogueService;
use App\Services\ServiceConsumableService;
use Illuminate\Http\Request;
use Throwable;

class ProcedureCatalogueController extends Controller
{
    public function __construct(
        private ProcedureCatalogueService $catalogue,
        private ServiceConsumableService $consumables,
    ) {}

    public function index(Request $request)
    {
        $services = $this->catalogue->listProcedureServices($request->get('search'));
        return view('admin.procedure-catalogue.index', compact('services'));
    }

    public function show(ServiceCatalog $service)
    {
        $this->catalogue->ensureProcedureService($service);

        $templateTypes = ProcedureCatalogueService::TEMPLATE_TYPES;
        $sectionsByType = [];
        $fieldsByType   = [];
        foreach ($templateTypes as $tt) {
            $sectionsByType[$tt] = $this->catalogue->sectionsFor($service, $tt);
            $fieldsByType[$tt]   = $this->catalogue->fieldsFor($service, $tt);
        }

        $serviceConsumables = $service->consumables()->with('product')->get();
        $availableProducts  = $this->consumables->availableProductsFor($service);
        $inputTypes         = ProcedureCatalogueService::INPUT_TYPES;

        return view('admin.procedure-catalogue.show', compact(
            'service', 'templateTypes', 'sectionsByType', 'fieldsByType',
            'serviceConsumables', 'availableProducts', 'inputTypes',
        ));
    }

    /* ── Sections ─────────────────────────────────── */

    public function storeSection(Request $request, ServiceCatalog $service)
    {
        $data = $request->validate([
            'template_type' => 'required|string',
            'name'          => 'required|string|max:191',
            'description'   => 'nullable|string|max:500',
            'sort_order'    => 'nullable|integer',
        ]);
        try {
            $this->catalogue->addSection($service, $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', __('messages.procedure_catalogue.section_added'));
    }

    public function updateSection(Request $request, ProcedureTemplateSection $section)
    {
        $data = $request->validate([
            'template_type' => 'sometimes|string',
            'name'          => 'sometimes|string|max:191',
            'description'   => 'sometimes|nullable|string|max:500',
            'sort_order'    => 'sometimes|integer',
            'is_active'     => 'sometimes|boolean',
        ]);
        try {
            $this->catalogue->updateSection($section, $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', __('messages.procedure_catalogue.section_updated'));
    }

    public function destroySection(ProcedureTemplateSection $section)
    {
        $this->catalogue->deleteSection($section);
        return back()->with('success', __('messages.procedure_catalogue.section_removed'));
    }

    /* ── Fields ─────────────────────────────────── */

    public function storeField(Request $request, ServiceCatalog $service)
    {
        $data = $request->validate([
            'template_type' => 'required|string',
            'section_id'    => 'nullable|integer|exists:procedure_template_sections,id',
            'label'         => 'required|string|max:191',
            'field_key'     => 'nullable|string|max:100',
            'input_type'    => 'required|string',
            'options'       => 'nullable|array',
            'default_value' => 'nullable|string|max:500',
            'is_required'   => 'nullable|boolean',
            'sort_order'    => 'nullable|integer',
        ]);
        try {
            $this->catalogue->addField($service, $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', __('messages.procedure_catalogue.field_added'));
    }

    public function updateField(Request $request, ProcedureTemplateField $field)
    {
        $data = $request->validate([
            'template_type' => 'sometimes|string',
            'section_id'    => 'sometimes|nullable|integer|exists:procedure_template_sections,id',
            'label'         => 'sometimes|string|max:191',
            'field_key'     => 'sometimes|string|max:100',
            'input_type'    => 'sometimes|string',
            'options'       => 'sometimes|nullable|array',
            'default_value' => 'sometimes|nullable|string|max:500',
            'is_required'   => 'sometimes|boolean',
            'sort_order'    => 'sometimes|integer',
            'is_active'     => 'sometimes|boolean',
        ]);
        try {
            $this->catalogue->updateField($field, $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', __('messages.procedure_catalogue.field_updated'));
    }

    public function destroyField(ProcedureTemplateField $field)
    {
        $this->catalogue->deleteField($field);
        return back()->with('success', __('messages.procedure_catalogue.field_removed'));
    }

    /* ── Consumables ─────────────────────────────────── */

    public function storeConsumable(Request $request, ServiceCatalog $service)
    {
        $data = $request->validate([
            'product_id'       => 'required|integer|exists:products,id',
            'default_quantity' => 'required|numeric|min:0.0001',
            'is_required'      => 'nullable|boolean',
            'notes'            => 'nullable|string',
        ]);
        try {
            $this->consumables->upsert($service, $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', __('messages.procedure_catalogue.consumable_saved'));
    }

    public function destroyConsumable(ServiceCatalog $service, int $product)
    {
        $this->consumables->delete($service, $product);
        return back()->with('success', __('messages.procedure_catalogue.consumable_removed'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyOrderSetItem;
use App\Models\ConsultationSpecialtyProfile;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAdminOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationSpecialtyOrderSetItemController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet, ConsultationSpecialtyAdminOptions $options)
    {
        $this->authorizeOrderSet($profile, $orderSet);

        $orderSet->loadCount('applications');

        return view('admin.consultation-specialties.order-sets.show', [
            'profile' => $profile,
            'orderSet' => $orderSet->load(['items' => fn ($query) => $query->ordered()]),
            'itemTypes' => ConsultationSpecialtyAdminOptions::ITEM_TYPES,
            'applyModes' => ConsultationSpecialtyAdminOptions::APPLY_MODES,
            'sectionKeys' => $profile->sections()->ordered()->pluck('section_key')->all(),
            'favoritableTypes' => $options->allowedFavoritableTypes(),
        ]);
    }

    public function store(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet, ConsultationSpecialtyAdminOptions $options)
    {
        $this->authorizeOrderSet($profile, $orderSet);
        $item = $orderSet->items()->create($this->validated($request, $profile, $options));
        $this->log('ORDER_SET_ITEM_CREATED', $item);

        return back()->with('success', __('consultation_specialties.admin.saved'));
    }

    public function update(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet, ConsultationSpecialtyOrderSetItem $item, ConsultationSpecialtyAdminOptions $options)
    {
        $this->authorizeOrderSet($profile, $orderSet);
        $this->authorizeItem($orderSet, $item);
        $item->update($this->validated($request, $profile, $options));
        $this->log('ORDER_SET_ITEM_UPDATED', $item);

        return back()->with('success', __('consultation_specialties.admin.updated'));
    }

    public function destroy(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet, ConsultationSpecialtyOrderSetItem $item)
    {
        $this->authorizeOrderSet($profile, $orderSet);
        $this->authorizeItem($orderSet, $item);
        $this->log('ORDER_SET_ITEM_DELETED', $item);
        $item->delete();

        return back()->with('success', __('consultation_specialties.admin.deleted'));
    }

    public function reorder(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet)
    {
        $this->authorizeOrderSet($profile, $orderSet);
        $data = $request->validate(['orders' => ['required', 'array'], 'orders.*' => ['integer', 'min:0']]);
        foreach ($data['orders'] as $id => $order) {
            $orderSet->items()->whereKey($id)->update(['sort_order' => $order]);
        }
        $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_ORDER_SET_ITEMS_REORDERED', ['order_set_id' => $orderSet->id]);

        return back()->with('success', __('consultation_specialties.admin.reordered'));
    }

    private function validated(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options): array
    {
        $data = $request->validate([
            'item_type' => ['required', Rule::in(ConsultationSpecialtyAdminOptions::ITEM_TYPES)],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_section' => ['nullable', 'string', 'max:100'],
            'target_field' => ['nullable', 'string', 'max:100'],
            'favoritable_type' => ['nullable', 'string', Rule::in($options->allowedFavoritableTypes())],
            'favoritable_id' => ['nullable', 'integer'],
            'code' => ['nullable', 'string', 'max:100', 'regex:'.ConsultationSpecialtyAdminOptions::SLUG_PATTERN],
            'payload_json' => ['nullable', 'string'],
            'apply_mode' => ['required', Rule::in(ConsultationSpecialtyAdminOptions::APPLY_MODES)],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'metadata_json' => ['nullable', 'string'],
        ]);

        if (! $options->safeModelExists($data['favoritable_type'] ?? null, $data['favoritable_id'] ?? null)) {
            back()->withErrors(['favoritable_type' => __('consultation_specialties.admin.unsafe_model_type')])->withInput()->throwResponse();
        }

        if (blank($data['favoritable_type'] ?? null)) {
            $data['favoritable_type'] = null;
            $data['favoritable_id'] = null;
        }

        $errors = [];
        $data['payload'] = $options->decodeJson($data['payload_json'] ?? null, 'payload_json', $errors);
        $data['metadata'] = $options->decodeJson($data['metadata_json'] ?? null, 'metadata_json', $errors);
        unset($data['payload_json'], $data['metadata_json']);

        if ($errors) {
            back()->withErrors($errors)->withInput()->throwResponse();
        }

        if ($data['apply_mode'] === 'patch_specialty_entry') {
            $sectionKeys = $profile->sections()->pluck('section_key')->all();
            if (! in_array($data['target_section'] ?? '', $sectionKeys, true)) {
                back()->withErrors(['target_section' => __('consultation_specialties.admin.invalid_target_section')])->withInput()->throwResponse();
            }

            if (! $options->fieldExists((string) $data['target_section'], $data['target_field'] ?? null)) {
                back()->withErrors(['target_field' => __('consultation_specialties.admin.invalid_target_field')])->withInput()->throwResponse();
            }

            if (! $options->patchPayloadIsSafe($data['payload'])) {
                back()->withErrors(['payload_json' => __('consultation_specialties.admin.patch_payload_requires_merge')])->withInput()->throwResponse();
            }
        }

        if ($data['apply_mode'] === 'create_task' && blank(data_get($data['payload'], 'title')) && blank($data['label'])) {
            back()->withErrors(['payload_json' => __('consultation_specialties.admin.task_payload_requires_title')])->withInput()->throwResponse();
        }

        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function authorizeOrderSet(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet): void
    {
        abort_unless((int) $orderSet->consultation_specialty_profile_id === (int) $profile->id, 404);
    }

    private function authorizeItem(ConsultationSpecialtyOrderSet $orderSet, ConsultationSpecialtyOrderSetItem $item): void
    {
        abort_unless((int) $item->consultation_specialty_order_set_id === (int) $orderSet->id, 404);
    }

    private function log(string $event, ConsultationSpecialtyOrderSetItem $item): void
    {
        $this->activity->log(LogModule::CONSULTATION, $event, [
            'order_set_id' => $item->consultation_specialty_order_set_id,
            'item_type' => $item->item_type,
            'apply_mode' => $item->apply_mode,
        ], $item, 'Consultation specialty order set item configuration changed.');
    }
}

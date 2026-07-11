<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Enums\FrontDesk\LostFoundCategory;
use App\Enums\FrontDesk\LostFoundStatus;
use App\Http\Controllers\Controller;
use App\Models\FrontDeskLostFoundItem;
use App\Services\FrontDesk\LostFoundService;
use Illuminate\Http\Request;

class LostFoundController extends Controller
{
    public function __construct(private LostFoundService $service) {}

    public function index(Request $request)
    {
        $logs = FrontDeskLostFoundItem::query()
            ->with(['createdBy', 'claimVerifiedBy', 'releasedBy'])
            ->search($request->string('search'))
            ->dateRange($request->input('date_from'), $request->input('date_to'))
            ->status($request->input('item_status'))
            ->category($request->input('item_category'))
            ->when($request->boolean('unclaimed'), fn ($q) => $q->unclaimed())
            ->latest('found_or_reported_at')
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.lost-found.index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'item_status', 'item_category', 'unclaimed']),
            'statuses' => LostFoundStatus::cases(),
            'categories' => LostFoundCategory::cases(),
        ]);
    }

    public function create()
    {
        return view('admin.front-desk.lost-found.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return redirect()->route('admin.front-desk.lost-found.index')
            ->with('success', __('front_desk.flash.lost_found_created'));
    }

    public function show(FrontDeskLostFoundItem $lostFound)
    {
        $lostFound->load(['createdBy', 'updatedBy', 'claimVerifiedBy', 'releasedBy']);

        return view('admin.front-desk.lost-found.show', ['log' => $lostFound]);
    }

    public function edit(FrontDeskLostFoundItem $lostFound)
    {
        return view('admin.front-desk.lost-found.edit', array_merge($this->formData(), ['log' => $lostFound]));
    }

    public function update(Request $request, FrontDeskLostFoundItem $lostFound)
    {
        $this->service->update($lostFound, $this->validated($request), $request->user());

        return redirect()->route('admin.front-desk.lost-found.show', $lostFound)
            ->with('success', __('front_desk.flash.lost_found_updated'));
    }

    public function claim(Request $request, FrontDeskLostFoundItem $lostFound)
    {
        $data = $request->validate([
            'claimed_by_name' => ['nullable', 'string', 'max:255'],
            'claimed_by_phone' => ['nullable', 'string', 'max:50'],
        ]);
        $this->service->markClaimed($lostFound, $data, $request->user());

        return back()->with('success', __('front_desk.flash.lost_found_claimed'));
    }

    public function release(Request $request, FrontDeskLostFoundItem $lostFound)
    {
        $data = $request->validate([
            'claimed_by_name' => ['nullable', 'string', 'max:255'],
            'claimed_by_phone' => ['nullable', 'string', 'max:50'],
        ]);
        $this->service->release($lostFound, $data, $request->user());

        return back()->with('success', __('front_desk.flash.lost_found_released'));
    }

    public function cancel(Request $request, FrontDeskLostFoundItem $lostFound)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->service->cancel($lostFound, $request->user(), $data['reason'] ?? null);

        return back()->with('success', __('front_desk.flash.lost_found_cancelled'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'item_status' => ['nullable', 'string'],
            'item_category' => ['required', 'string'],
            'item_description' => ['required', 'string', 'max:2000'],
            'found_or_reported_at' => ['nullable', 'date'],
            'found_location' => ['nullable', 'string', 'max:255'],
            'found_by_name' => ['nullable', 'string', 'max:255'],
            'reported_by_name' => ['nullable', 'string', 'max:255'],
            'reported_by_phone' => ['nullable', 'string', 'max:50'],
            'stored_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'statuses' => LostFoundStatus::cases(),
            'categories' => LostFoundCategory::cases(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Billing;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SponsorController extends Controller
{
    public function __construct(protected ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $this->authorizeAny($request, ['sponsors.view', 'sponsors.manage']);

        $query = Sponsor::withCount('invoices')
            ->withSum(['invoices as outstanding_balance' => fn ($q) => $q->whereIn('status', ['pending', 'partially_paid'])], 'balance')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sponsors = $query->paginate(20)->withQueryString();
        $user = $request->user();

        $stats = [
            'total' => Sponsor::count(),
            'active' => Sponsor::where('is_active', true)->count(),
        ];
        $canManage = $user?->can('sponsors.manage') ?? false;

        return view('billing.sponsors.index', compact('sponsors', 'stats', 'canManage'));
    }

    public function store(Request $request)
    {
        $this->authorizeAny($request, ['sponsors.create', 'sponsors.manage']);

        $data = $this->validateSponsor($request);
        $data['code'] = ($data['code'] ?? null) ?: $this->generateCode($data['name']);

        $sponsor = Sponsor::create($data);

        $this->log('SPONSOR_CREATED', $sponsor, [
            'new_values' => $sponsor->getAttributes(),
        ]);

        return back()->with('success', __('messages.billing.sponsor_created', ['name' => $data['name']]));
    }

    public function update(Request $request, Sponsor $sponsor)
    {
        $this->authorizeAny($request, ['sponsors.edit', 'sponsors.manage']);

        $data = $this->validateSponsor($request, $sponsor);

        $old = $sponsor->getOriginal();
        $sponsor->update($data);

        $this->log('SPONSOR_UPDATED', $sponsor, [
            'old_values' => $old,
            'new_values' => $sponsor->fresh()->getAttributes(),
        ]);

        return back()->with('success', __('messages.billing.sponsor_updated', ['name' => $sponsor->name]));
    }

    public function toggle(Sponsor $sponsor)
    {
        $this->authorizeAny(request(), ['sponsors.edit', 'sponsors.manage']);

        $old = $sponsor->getOriginal();
        $sponsor->update(['is_active' => ! $sponsor->is_active]);

        $this->log('SPONSOR_STATUS_TOGGLED', $sponsor, [
            'old_values' => $old,
            'new_values' => $sponsor->fresh()->getAttributes(),
        ]);

        return back()->with('success', __('messages.billing.sponsor_toggled', ['name' => $sponsor->name, 'status' => $sponsor->is_active ? 'activated' : 'deactivated']));
    }

    private function validateSponsor(Request $request, ?Sponsor $sponsor = null): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('sponsors', 'code')->ignore($sponsor?->id)],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function generateCode(string $name): string
    {
        $base = strtoupper(Str::slug(Str::limit($name, 6, ''), ''));
        $base = $base !== '' ? $base : 'SPN';

        do {
            $code = $base . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Sponsor::where('code', $code)->exists());

        return $code;
    }

    private function authorizeAny(Request $request, array $permissions): void
    {
        $user = $request->user();
        foreach ($permissions as $permission) {
            if ($user?->can($permission)) {
                return;
            }
        }

        abort(403);
    }

    private function log(string $action, Sponsor $sponsor, array $context = []): void
    {
        $this->logger->log(
            LogModule::BILLING,
            $action,
            array_merge([
                'severity' => LogSeverity::INFO,
                'sponsor_id' => $sponsor->id,
            ], $context),
            $sponsor,
            str_replace('_', ' ', ucfirst(strtolower($action)))
        );
    }
}

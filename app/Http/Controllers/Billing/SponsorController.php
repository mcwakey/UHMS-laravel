<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SponsorController extends Controller
{
    public function index(Request $request)
    {
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

        return Inertia::render('Billing/Sponsors/Index', [
            'sponsors' => $sponsors->through(fn (Sponsor $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'contact_person' => $s->contact_person,
                'email' => $s->email,
                'phone' => $s->phone,
                'address' => $s->address,
                'credit_limit' => $s->credit_limit !== null ? (float) $s->credit_limit : null,
                'is_active' => (bool) $s->is_active,
                'notes' => $s->notes,
                'invoices_count' => (int) $s->invoices_count,
                'outstanding_balance' => (float) ($s->outstanding_balance ?? 0),
            ]),
            'stats' => [
                'total' => Sponsor::count(),
                'active' => Sponsor::where('is_active', true)->count(),
            ],
            'filters' => $request->only(['search', 'status']),
            'routes' => [
                'index' => route('admin.billing.sponsors.index'),
                'store' => route('admin.billing.sponsors.store'),
            ],
            'can' => [
                'manage' => $user?->can('sponsors.manage') ?? false,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSponsor($request);
        $data['code'] = ($data['code'] ?? null) ?: $this->generateCode($data['name']);

        Sponsor::create($data);

        return back()->with('success', "Sponsor {$data['name']} created.");
    }

    public function update(Request $request, Sponsor $sponsor)
    {
        $data = $this->validateSponsor($request, $sponsor);

        $sponsor->update($data);

        return back()->with('success', "Sponsor {$sponsor->name} updated.");
    }

    public function toggle(Sponsor $sponsor)
    {
        $sponsor->update(['is_active' => ! $sponsor->is_active]);

        return back()->with('success', "Sponsor {$sponsor->name} " . ($sponsor->is_active ? 'activated' : 'deactivated') . '.');
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
}

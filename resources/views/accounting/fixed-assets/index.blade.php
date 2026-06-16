@extends('layouts.app')
@section('title', 'Fixed Assets')

@php $money = fn ($n) => 'GHS '.number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="Fixed Assets" icon="ti-building-factory-2" description="Asset register, capitalization, depreciation and disposal foundation." />

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Create Category</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounting.fixed-assets.categories.store') }}" class="row g-2">
                    @csrf
                    <div class="col-7"><input name="name" class="form-control" placeholder="Category name" required></div>
                    <div class="col-5"><input name="code" class="form-control" placeholder="Code" required></div>
                    <div class="col-12"><select name="asset_cost_account_id" class="form-select" required><option value="">Cost account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-12"><select name="accumulated_depreciation_account_id" class="form-select" required><option value="">Accum depreciation account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-12"><select name="depreciation_expense_account_id" class="form-select" required><option value="">Depreciation expense account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-6"><select name="disposal_gain_account_id" class="form-select"><option value="">Gain account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-6"><select name="disposal_loss_account_id" class="form-select"><option value="">Loss account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-6"><input name="useful_life_months" type="number" min="1" value="60" class="form-control" placeholder="Life months"></div>
                    <div class="col-6"><input name="default_residual_rate" type="number" step="0.01" min="0" max="100" value="0" class="form-control" placeholder="Residual %"></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100">Save Category</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Create Location</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounting.fixed-assets.locations.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><input name="name" class="form-control" placeholder="Location name" required></div>
                    <div class="col-12"><input name="code" class="form-control" placeholder="Code" required></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100">Save Location</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Register Asset</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounting.fixed-assets.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-6"><input name="name" class="form-control" placeholder="Asset name" required></div>
                    <div class="col-md-6"><select name="asset_category_id" class="form-select" required><option value="">Category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><select name="asset_location_id" class="form-select"><option value="">Location</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><input name="cost" type="number" step="0.01" min="0.01" class="form-control" placeholder="Cost" required></div>
                    <div class="col-md-6"><input name="acquisition_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-6"><input name="placed_in_service_date" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
                    <div class="col-md-6"><input name="useful_life_months" type="number" min="1" class="form-control" placeholder="Life months"></div>
                    <div class="col-md-6"><input name="residual_value" type="number" step="0.01" min="0" class="form-control" placeholder="Residual value"></div>
                    <div class="col-12"><button class="btn btn-primary w-100">Register Asset</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Depreciation Runs</h5>
        <form method="POST" action="{{ route('admin.accounting.fixed-assets.depreciation.run') }}" class="d-flex gap-2">
            @csrf
            <select name="accounting_period_id" class="form-select form-select-sm" required>@foreach($periods as $period)<option value="{{ $period->id }}">{{ $period->name }}</option>@endforeach</select>
            <button class="btn btn-sm btn-outline-primary">Run</button>
        </form>
    </div>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Run</th><th>Period</th><th class="text-end">Total</th><th>Journal</th></tr></thead><tbody>
        @forelse($runs as $run)<tr><td>{{ $run->run_number }}</td><td>{{ $run->period_start?->format('d M Y') }} - {{ $run->period_end?->format('d M Y') }}</td><td class="text-end">{{ $money($run->total_depreciation) }}</td><td>{{ $run->journalEntry?->journal_number }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-3">No depreciation runs yet.</td></tr>@endforelse
    </tbody></table></div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Asset</th><th>Category</th><th>Status</th><th class="text-end">Cost</th><th class="text-end">Accum Dep</th><th class="text-end">Carrying</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($assets as $asset)
                    <tr>
                        <td><strong>{{ $asset->asset_number }}</strong><small class="d-block text-muted">{{ $asset->name }}</small></td>
                        <td>{{ $asset->category?->name }}</td>
                        <td><span class="badge bg-secondary">{{ $asset->status }}</span></td>
                        <td class="text-end">{{ $money($asset->cost) }}</td>
                        <td class="text-end">{{ $money($asset->accumulated_depreciation) }}</td>
                        <td class="text-end fw-semibold">{{ $money($asset->carrying_amount) }}</td>
                        <td class="text-end">
                            @if($asset->status === \App\Models\FixedAsset::STATUS_DRAFT)
                                <form method="POST" action="{{ route('admin.accounting.fixed-assets.capitalize', $asset) }}" class="d-inline-flex gap-1">
                                    @csrf
                                    <select name="credit_account_id" class="form-select form-select-sm" style="width: 160px">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                                    <button class="btn btn-sm btn-success">Capitalize</button>
                                </form>
                            @elseif($asset->status === \App\Models\FixedAsset::STATUS_ACTIVE)
                                <form method="POST" action="{{ route('admin.accounting.fixed-assets.verify', $asset) }}" class="d-inline">@csrf<input type="hidden" name="verification_date" value="{{ now()->toDateString() }}"><input type="hidden" name="condition_status" value="good"><button class="btn btn-sm btn-outline-secondary">Verify</button></form>
                                <form method="POST" action="{{ route('admin.accounting.fixed-assets.dispose', $asset) }}" class="d-inline-flex gap-1">@csrf<input type="hidden" name="disposal_date" value="{{ now()->toDateString() }}"><input name="proceeds_amount" type="number" step="0.01" min="0" class="form-control form-control-sm" style="width: 110px" placeholder="Proceeds"><select name="proceeds_account_id" class="form-select form-select-sm" style="width: 140px"><option value="">No proceeds</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }}</option>@endforeach</select><button class="btn btn-sm btn-outline-danger">Dispose</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No fixed assets registered.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

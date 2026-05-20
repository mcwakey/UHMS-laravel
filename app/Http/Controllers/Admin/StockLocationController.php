<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockLocationController extends Controller
{
    public function index()
    {
        $this->ensureMainStoreExists();
        $locations   = StockLocation::with('department')->orderByDesc('is_main')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $types       = ['store', 'pharmacy', 'lab', 'theatre', 'ward', 'xray', 'scan', 'other'];
        return view('admin.stock-locations.index', compact('locations', 'departments', 'types'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        DB::transaction(function () use ($data) {
            if (! empty($data['is_main'])) {
                if (StockLocation::query()->where('is_main', true)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['is_main' => 'Main Store already exists.']);
                }
                StockLocation::query()->where('is_main', true)->update(['is_main' => false]);
            }
            StockLocation::create($data);
        });
        return back()->with('success', 'Stock location created.');
    }

    public function update(Request $request, StockLocation $stockLocation)
    {
        if ($stockLocation->is_main) {
            return back()->with('error', 'Main Store is a protected system location and cannot be edited here.');
        }

        $data = $this->validatePayload($request, $stockLocation->id);
        DB::transaction(function () use ($data, $stockLocation) {
            if (! empty($data['is_main'])) {
                StockLocation::query()->where('id', '!=', $stockLocation->id)->where('is_main', true)->update(['is_main' => false]);
            }
            $stockLocation->update($data);
        });
        return back()->with('success', 'Stock location updated.');
    }

    public function toggle(StockLocation $stockLocation)
    {
        if ($stockLocation->is_main) {
            return back()->with('error', 'Main Store cannot be deactivated.');
        }

        $stockLocation->update(['is_active' => ! $stockLocation->is_active]);
        return back()->with('success', 'Status toggled.');
    }

    private function ensureMainStoreExists(): StockLocation
    {
        $departmentId = Department::query()
            ->where('name', 'like', '%Store%')
            ->orWhere('name', 'like', '%Procurement%')
            ->value('id');

        $main = StockLocation::query()->where('is_main', true)->first();
        if ($main) {
            if (! $main->is_active || $main->name !== 'Main Store') {
                $main->forceFill(['name' => 'Main Store', 'is_active' => true])->save();
            }
            return $main;
        }

        return StockLocation::updateOrCreate(
            ['name' => 'Main Store'],
            [
                'type' => 'store',
                'department_id' => $departmentId,
                'is_active' => true,
                'is_main' => true,
            ],
        );
    }

    protected function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'          => 'required|string|max:191|unique:stock_locations,name'.($ignoreId ? ",{$ignoreId}" : ''),
            'type'          => 'required|string|max:30',
            'department_id' => 'nullable|integer|exists:departments,id',
            'is_active'     => 'nullable|boolean',
            'is_main'       => 'nullable|boolean',
            'notes'         => 'nullable|string|max:500',
        ]);
    }
}

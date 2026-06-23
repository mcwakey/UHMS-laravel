<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\IcdCode;
use Illuminate\Http\Request;

class IcdCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = IcdCode::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('chapter')) {
            $query->byChapter($request->chapter);
        }

        $codes = $query->orderBy('code')->paginate(50)->withQueryString();
        $chapters = IcdCode::select('chapter')->distinct()->whereNotNull('chapter')->orderBy('chapter')->pluck('chapter');

        return view('admin.icd-codes.index', compact('codes', 'chapters'));
    }

    public function search(Request $request)
    {
        $request->validate(['q' => ['required', 'string', 'min:2']]);

        $results = IcdCode::search($request->q)
            ->select('id', 'code', 'description', 'category')
            ->limit(20)
            ->get()
            ->map(fn ($code) => [
                'id' => $code->id,
                'text' => "{$code->code} — {$code->description}",
                'code' => $code->code,
                'description' => $code->description,
            ]);

        return response()->json(['results' => $results]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:icd_codes,code'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'chapter' => ['nullable', 'string', 'max:10'],
            'is_billable' => ['nullable', 'boolean'],
        ]);

        $data['is_billable'] = $request->boolean('is_billable', true);
        IcdCode::create($data);

        return back()->with('success', __('messages.icd_codes.created'));
    }

    public function update(Request $request, IcdCode $icdCode)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:icd_codes,code,' . $icdCode->id],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'chapter' => ['nullable', 'string', 'max:10'],
            'is_billable' => ['nullable', 'boolean'],
        ]);

        $data['is_billable'] = $request->boolean('is_billable', true);
        $icdCode->update($data);

        return back()->with('success', __('messages.icd_codes.updated'));
    }

    public function destroy(IcdCode $icdCode)
    {
        if ($icdCode->diagnoses()->exists()) {
            return back()->with('error', __('messages.icd_codes.cannot_delete'));
        }

        $icdCode->delete();

        return back()->with('success', __('messages.icd_codes.deleted'));
    }
}

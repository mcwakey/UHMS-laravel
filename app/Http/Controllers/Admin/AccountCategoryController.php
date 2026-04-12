<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountCategoryRequest;
use App\Models\AccountCategory;
use Illuminate\Http\Request;

class AccountCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = AccountCategory::query()
            ->withCount('entries')
            ->when($request->search, fn ($q, $s) => $q->search($s))
            ->when($request->type, fn ($q, $t) => $q->byType($t))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(20);

        $types = EntryType::cases();

        return view('accounts.categories', compact('categories', 'types'));
    }

    public function store(StoreAccountCategoryRequest $request)
    {
        AccountCategory::create($request->validated());

        return redirect()
            ->route('admin.accounts.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function update(StoreAccountCategoryRequest $request, AccountCategory $category)
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.accounts.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function toggle(AccountCategory $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', 'Category status updated.');
    }
}

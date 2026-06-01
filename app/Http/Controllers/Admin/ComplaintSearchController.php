<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplaintSearchService;
use Illuminate\Http\Request;

class ComplaintSearchController extends Controller
{
    public function __invoke(Request $request, ComplaintSearchService $complaints)
    {
        $results = $complaints->search($request->query('q'), (int) $request->query('limit', 15));

        return response()->json($complaints->autocompletePayload($results));
    }
}
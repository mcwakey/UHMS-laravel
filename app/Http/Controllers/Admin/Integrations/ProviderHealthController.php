<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\ProviderHealthService;

class ProviderHealthController extends Controller
{
    public function __construct(protected ProviderHealthService $health) {}

    public function index()
    {
        $rows = $this->health->snapshot();
        $this->health->logHealthChecked();

        return view('admin.integrations.health.index', compact('rows'));
    }
}

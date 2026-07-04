<?php

namespace App\Http\Controllers\Admin\Maternity;

use App\Http\Controllers\Controller;
use App\Services\Maternity\MaternityOverviewService;

class MaternityDashboardController extends Controller
{
    public function __construct(private MaternityOverviewService $overview) {}

    public function __invoke()
    {
        return view('maternity.dashboard', [
            'overview' => $this->overview->dashboard(),
        ]);
    }
}

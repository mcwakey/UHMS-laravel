<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\SmsDeliveryReport;
use App\Models\SmsProviderCallback;

class SmsDeliveryReportController extends Controller
{
    public function index()
    {
        $reports = SmsDeliveryReport::query()
            ->with(['provider', 'recipient.message'])
            ->latest()
            ->paginate(20);

        $callbacks = SmsProviderCallback::query()
            ->latest()
            ->paginate(20, ['*'], 'callbacks');

        return view('admin.integrations.sms.delivery-reports.index', compact('reports', 'callbacks'));
    }
}

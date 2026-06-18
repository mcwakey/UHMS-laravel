<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\SmsNotificationEvent;
use App\Services\ActivityLogService;
use App\Services\Integrations\Sms\SmsEventSettingsService;
use Illuminate\Http\Request;

class SmsEventController extends Controller
{
    public function __construct(
        protected SmsEventSettingsService $settings,
        protected ActivityLogService $logger,
    ) {}

    public function index()
    {
        $toggles = $this->settings->all();
        $events = SmsNotificationEvent::query()->with('message')->latest()->paginate(20);

        return view('admin.integrations.sms.events.index', compact('toggles', 'events'));
    }

    public function update(Request $request)
    {
        $values = [
            'enable_payment_request_sms' => $request->boolean('enable_payment_request_sms'),
            'enable_receipt_sms' => $request->boolean('enable_receipt_sms'),
            'enable_appointment_reminder_sms' => $request->boolean('enable_appointment_reminder_sms'),
            'enable_queue_sms' => $request->boolean('enable_queue_sms'),
        ];
        $this->settings->setMany($values);

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_NOTIFICATION_EVENT_SETTINGS_UPDATED', [
            'source_type' => 'settings',
            'new_values' => $values,
        ], null, 'Automatic SMS event settings updated');

        return back()->with('success', __('sms.flash.events_updated'));
    }
}

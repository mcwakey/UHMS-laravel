<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Services\Integrations\Sms\SmsGatewayService;
use App\Services\Integrations\Sms\SmsStatusReconciliationService;
use Illuminate\Http\Request;

class SmsQueueController extends Controller
{
    public function __construct(protected SmsGatewayService $gateway) {}

    public function index(Request $request)
    {
        $messages = SmsMessage::query()
            ->with('provider')
            ->withCount([
                'recipients',
                'recipients as failed_count' => fn ($q) => $q->whereIn('status', ['failed', 'undelivered']),
            ])
            ->whereIn('status', [
                SmsMessage::STATUS_QUEUED, SmsMessage::STATUS_SENDING,
                SmsMessage::STATUS_PARTIALLY_SENT, SmsMessage::STATUS_FAILED,
            ])
            ->latest()
            ->paginate(20);

        $queueDriver = (string) config('queue.default');

        return view('admin.integrations.sms.queue.index', compact('messages', 'queueDriver'));
    }

    public function retry(SmsMessage $message)
    {
        $this->gateway->retry($message);

        return back()->with('success', __('sms.flash.retry_started'));
    }

    public function reconcile(Request $request, SmsStatusReconciliationService $service)
    {
        $summary = $service->reconcile(['limit' => 200]);

        return back()->with('success', __('sms.flash.reconciliation_run', [
            'checked' => $summary['checked'], 'delivered' => $summary['delivered'],
        ]));
    }
}

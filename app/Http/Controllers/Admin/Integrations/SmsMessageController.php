<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Exceptions\Integrations\IntegrationException;
use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\Integrations\Sms\SmsGatewayService;
use Illuminate\Http\Request;

class SmsMessageController extends Controller
{
    public function __construct(protected SmsGatewayService $gateway) {}

    public function index(Request $request)
    {
        $messages = SmsMessage::query()
            ->with(['provider'])
            ->withCount('recipients')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.integrations.sms.messages.index', compact('messages'));
    }

    public function create()
    {
        $templates = SmsTemplate::active()->orderBy('name')->get();
        $hasProvider = $this->gateway->hasActiveProvider();

        return view('admin.integrations.sms.messages.create', compact('templates', 'hasProvider'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'message_body' => ['required', 'string', 'max:1000'],
            'recipients' => ['required', 'string'],          // newline/comma separated phone numbers
            'sender_id' => ['nullable', 'string', 'max:60'],
            'template_id' => ['nullable', 'integer', 'exists:sms_templates,id'],
        ]);

        $recipients = collect(preg_split('/[\s,;]+/', $data['recipients']))
            ->map(fn ($p) => trim((string) $p))
            ->filter()
            ->map(fn ($p) => ['phone' => $p])
            ->values()
            ->all();

        if ($recipients === []) {
            return back()->withInput()->with('error', __('integrations.flash.no_recipients'));
        }

        try {
            $message = $this->gateway->send([
                'body' => $data['message_body'],
                'recipients' => $recipients,
                'sender_id' => $data['sender_id'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'message_type' => 'manual',
            ]);
        } catch (IntegrationException $e) {
            return back()->withInput()->with('error', $e->localisedMessage());
        }

        return redirect()
            ->route('admin.integrations.sms.messages.show', $message)
            ->with('success', __('integrations.flash.sms_queued'));
    }

    public function show(SmsMessage $message)
    {
        $message->load(['provider', 'recipients.deliveryReports', 'template']);

        return view('admin.integrations.sms.messages.show', compact('message'));
    }

    public function resend(SmsMessage $message)
    {
        try {
            $this->gateway->resend($message);
        } catch (IntegrationException $e) {
            return back()->with('error', $e->localisedMessage());
        }

        return back()->with('success', __('integrations.flash.sms_queued'));
    }
}

<?php

namespace App\Services\Integrations\Sms;

use App\Enums\LogModule;
use App\Models\SmsTemplate;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class SmsTemplateService
{
    public function __construct(protected ActivityLogService $logger) {}

    public function create(array $data): SmsTemplate
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $template = SmsTemplate::create($data);

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_TEMPLATE_CREATED', [
            'source_type' => 'sms_template',
            'source_id' => $template->id,
            'new_values' => $template->only(['code', 'name', 'language', 'is_active']),
        ], $template, "SMS template created: {$template->code}");

        return $template;
    }

    public function update(SmsTemplate $template, array $data): SmsTemplate
    {
        $old = $template->only(['code', 'name', 'language', 'body', 'is_active']);
        $data['updated_by'] = Auth::id();
        $template->update($data);

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_TEMPLATE_UPDATED', [
            'source_type' => 'sms_template',
            'source_id' => $template->id,
            'old_values' => $old,
            'new_values' => $template->only(['code', 'name', 'language', 'body', 'is_active']),
        ], $template, "SMS template updated: {$template->code}");

        return $template;
    }
}

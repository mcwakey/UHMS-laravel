<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use App\Services\Integrations\Sms\SmsTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsTemplateController extends Controller
{
    public function __construct(protected SmsTemplateService $templates) {}

    public function index()
    {
        $templates = SmsTemplate::orderBy('name')->paginate(20);

        return view('admin.integrations.sms.templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('sms_templates', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'language' => ['required', 'string', 'max:5'],
            'body' => ['required', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $this->templates->create($data);

        return back()->with('success', __('integrations.flash.template_created'));
    }

    public function update(Request $request, SmsTemplate $template)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('sms_templates', 'code')->ignore($template->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'language' => ['required', 'string', 'max:5'],
            'body' => ['required', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $this->templates->update($template, $data);

        return back()->with('success', __('integrations.flash.template_updated'));
    }
}

<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\IntegrationProvider;
use App\Services\Integrations\IntegrationCredentialService;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Services\Integrations\IntegrationProviderService;
use Illuminate\Http\Request;

class SmsProviderController extends Controller
{
    public function __construct(
        protected IntegrationProviderService $providers,
        protected IntegrationProviderRegistry $registry,
        protected IntegrationCredentialService $credentials,
    ) {}

    public function index()
    {
        $providers = IntegrationProvider::sms()->orderByDesc('is_active')->orderBy('name')->get();
        $catalogue = $this->registry->catalogue(IntegrationProvider::MODULE_SMS);

        return view('admin.integrations.sms.providers.index', compact('providers', 'catalogue'));
    }

    public function create()
    {
        $catalogue = $this->registry->catalogue(IntegrationProvider::MODULE_SMS);

        return view('admin.integrations.sms.providers.create', compact('catalogue'));
    }

    public function store(Request $request)
    {
        $data = $this->validateProvider($request);
        $data = array_merge($data, IntegrationProviderService::capabilityFlags(IntegrationProvider::MODULE_SMS, $data['code']));

        $provider = $this->providers->create($data, IntegrationProvider::MODULE_SMS);

        if ($request->filled('credentials')) {
            $this->providers->updateCredentials($provider, (array) $request->input('credentials'));
        }

        return redirect()
            ->route('admin.integrations.sms.providers.edit', $provider)
            ->with('success', __('integrations.flash.provider_created'));
    }

    public function edit(IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_SMS, 404);

        $configuredKeys = $this->credentials->configuredKeys($provider);
        $credentialKeys = $this->credentialKeysFor($provider->code);

        return view('admin.integrations.sms.providers.edit', compact('provider', 'configuredKeys', 'credentialKeys'));
    }

    public function update(Request $request, IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_SMS, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'environment' => ['required', 'in:sandbox,live'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'sender_id' => ['nullable', 'string', 'max:60'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'webhook_secret_hint' => ['nullable', 'string', 'max:255'],
        ]);

        $this->providers->update($provider, $data);

        return back()->with('success', __('integrations.flash.provider_updated'));
    }

    public function updateCredentials(Request $request, IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_SMS, 404);

        $request->validate(['credentials' => ['required', 'array']]);
        $this->providers->updateCredentials($provider, (array) $request->input('credentials'));

        return back()->with('success', __('integrations.flash.credentials_updated'));
    }

    public function activate(IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_SMS, 404);

        $this->providers->activate($provider);

        return back()->with('success', __('integrations.flash.provider_activated', ['name' => $provider->name]));
    }

    public function deactivate(IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_SMS, 404);

        $this->providers->deactivate($provider);

        return back()->with('success', __('integrations.flash.provider_deactivated', ['name' => $provider->name]));
    }

    public function test(IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_SMS, 404);

        $result = $this->providers->test($provider);

        return back()->with($result->success ? 'success' : 'error', $result->message);
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function validateProvider(Request $request): array
    {
        $codes = array_column($this->registry->catalogue(IntegrationProvider::MODULE_SMS), 'code');

        return $request->validate([
            'code' => ['required', 'string', 'in:' . implode(',', $codes)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'environment' => ['required', 'in:sandbox,live'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'sender_id' => ['nullable', 'string', 'max:60'],
            'callback_url' => ['nullable', 'url', 'max:255'],
        ]);
    }

    /** Expected credential keys per provider code (for the dynamic form). */
    private function credentialKeysFor(string $code): array
    {
        return match ($code) {
            'nalo_sms' => ['api_key', 'username', 'password', 'sender_id', 'client_id', 'client_secret'],
            default => [],
        };
    }
}

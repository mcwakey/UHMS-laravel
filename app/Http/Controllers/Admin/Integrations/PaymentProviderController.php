<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\IntegrationProvider;
use App\Services\Integrations\IntegrationCredentialService;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Services\Integrations\IntegrationProviderService;
use Illuminate\Http\Request;

class PaymentProviderController extends Controller
{
    public function __construct(
        protected IntegrationProviderService $providers,
        protected IntegrationProviderRegistry $registry,
        protected IntegrationCredentialService $credentials,
    ) {}

    public function index()
    {
        $providers = IntegrationProvider::payment()->orderByDesc('is_active')->orderBy('name')->get();
        $catalogue = $this->registry->catalogue(IntegrationProvider::MODULE_PAYMENT);

        return view('admin.integrations.payments.providers.index', compact('providers', 'catalogue'));
    }

    public function create()
    {
        $catalogue = $this->registry->catalogue(IntegrationProvider::MODULE_PAYMENT);

        return view('admin.integrations.payments.providers.create', compact('catalogue'));
    }

    public function store(Request $request)
    {
        $codes = array_column($this->registry->catalogue(IntegrationProvider::MODULE_PAYMENT), 'code');

        $data = $request->validate([
            'code' => ['required', 'string', 'in:' . implode(',', $codes)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'environment' => ['required', 'in:sandbox,live'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'callback_url' => ['nullable', 'url', 'max:255'],
        ]);
        $data = array_merge($data, IntegrationProviderService::capabilityFlags(IntegrationProvider::MODULE_PAYMENT, $data['code']));

        $provider = $this->providers->create($data, IntegrationProvider::MODULE_PAYMENT);

        if ($request->filled('credentials')) {
            $this->providers->updateCredentials($provider, (array) $request->input('credentials'));
        }

        return redirect()
            ->route('admin.integrations.payments.providers.edit', $provider)
            ->with('success', __('integrations.flash.provider_created'));
    }

    public function edit(IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_PAYMENT, 404);

        $configuredKeys = $this->credentials->configuredKeys($provider);
        $credentialKeys = $this->credentialKeysFor($provider->code);

        return view('admin.integrations.payments.providers.edit', compact('provider', 'configuredKeys', 'credentialKeys'));
    }

    public function update(Request $request, IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_PAYMENT, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'environment' => ['required', 'in:sandbox,live'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'webhook_secret_hint' => ['nullable', 'string', 'max:255'],
        ]);

        $this->providers->update($provider, $data);

        return back()->with('success', __('integrations.flash.provider_updated'));
    }

    public function updateCredentials(Request $request, IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_PAYMENT, 404);

        $request->validate(['credentials' => ['required', 'array']]);
        $this->providers->updateCredentials($provider, (array) $request->input('credentials'));

        return back()->with('success', __('integrations.flash.credentials_updated'));
    }

    public function activate(Request $request, IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_PAYMENT, 404);

        $override = $request->boolean('override') && (bool) $request->user()?->can('integrations.payments.golive.approve');

        try {
            $this->providers->activate($provider, $override, $request->input('override_reason'));
        } catch (\App\Exceptions\Integrations\IntegrationException $e) {
            return back()->with('error', $e->localisedMessage());
        }

        return back()->with('success', __('integrations.flash.provider_activated', ['name' => $provider->name]));
    }

    public function deactivate(IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_PAYMENT, 404);

        $this->providers->deactivate($provider);

        return back()->with('success', __('integrations.flash.provider_deactivated', ['name' => $provider->name]));
    }

    public function test(IntegrationProvider $provider)
    {
        abort_unless($provider->module_type === IntegrationProvider::MODULE_PAYMENT, 404);

        $result = $this->providers->test($provider);

        return back()->with($result->success ? 'success' : 'error', $result->message);
    }

    private function credentialKeysFor(string $code): array
    {
        return match ($code) {
            'mtn_momo' => ['subscription_key', 'api_user', 'api_key', 'target_environment', 'collection_primary_key', 'callback_secret', 'merchant_account_reference'],
            'nalo_payment' => ['merchant_id', 'basic_auth_token', 'secret_key'],
            default => [],
        };
    }
}

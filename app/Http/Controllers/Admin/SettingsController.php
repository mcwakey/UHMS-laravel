<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Organization settings.
     */
    public function organization()
    {
        $settings = Setting::getGroup('organization');

        return view('settings.organization', compact('settings'));
    }

    public function updateOrganization(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:191',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            Setting::setValue('organization', 'logo', $path);
        }

        unset($validated['logo']);

        foreach ($validated as $key => $value) {
            Setting::setValue('organization', $key, $value ?? '');
        }

        return back()->with('success', 'Organization settings updated successfully.');
    }

    /**
     * Invoice settings.
     */
    public function invoice()
    {
        $settings = Setting::getGroup('invoice');

        return view('settings.invoice', compact('settings'));
    }

    public function updateInvoice(Request $request)
    {
        $validated = $request->validate([
            'prefix' => 'required|string|max:10',
            'due_days' => 'required|integer|min:1|max:365',
            'tax_enabled' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_label' => 'nullable|string|max:50',
            'footer_note' => 'nullable|string|max:500',
            'terms' => 'nullable|string|max:2000',
        ]);

        $validated['tax_enabled'] = $request->boolean('tax_enabled') ? '1' : '0';

        foreach ($validated as $key => $value) {
            Setting::setValue('invoice', $key, $value ?? '');
        }

        return back()->with('success', 'Invoice settings updated successfully.');
    }

    /**
     * Payment methods settings.
     */
    public function paymentMethods()
    {
        $settings = Setting::getGroup('payment');

        return view('settings.payment-methods', compact('settings'));
    }

    public function updatePaymentMethods(Request $request)
    {
        $validated = $request->validate([
            'cash_enabled' => 'nullable|boolean',
            'momo_enabled' => 'nullable|boolean',
            'card_enabled' => 'nullable|boolean',
            'insurance_enabled' => 'nullable|boolean',
            'bank_transfer_enabled' => 'nullable|boolean',
            'momo_merchant_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:100',
        ]);

        $booleanFields = ['cash_enabled', 'momo_enabled', 'card_enabled', 'insurance_enabled', 'bank_transfer_enabled'];
        foreach ($booleanFields as $field) {
            $validated[$field] = $request->boolean($field) ? '1' : '0';
        }

        foreach ($validated as $key => $value) {
            Setting::setValue('payment', $key, $value ?? '');
        }

        return back()->with('success', 'Payment method settings updated successfully.');
    }
}

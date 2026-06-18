<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\PaymentProviderCallback;

/**
 * Internal admin view of stored payment callbacks (read-only).
 */
class PaymentCallbackController extends Controller
{
    public function index()
    {
        $callbacks = PaymentProviderCallback::query()
            ->with('provider')
            ->latest()
            ->paginate(25);

        return view('admin.integrations.payments.callbacks.index', compact('callbacks'));
    }
}

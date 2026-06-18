<?php

use App\Http\Controllers\Api\Integrations\PaymentCallbackController;
use App\Http\Controllers\Api\Integrations\SmsCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public, session-less, CSRF-exempt endpoints. Used by external integration
| providers to deliver asynchronous callbacks/webhooks. Authentication is via
| per-provider signature/secret verification (handled in the controllers and
| provider adapters), NOT browser auth. Every callback is stored before any
| processing and processing is idempotent.
|
*/

Route::middleware('throttle:integration-callbacks')
    ->prefix('integrations')
    ->name('api.integrations.')
    ->group(function () {
        Route::post('sms/{providerCode}/callback', SmsCallbackController::class)
            ->name('sms.callback');

        Route::post('payments/{providerCode}/callback', PaymentCallbackController::class)
            ->name('payments.callback');
    });

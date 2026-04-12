<?php

namespace App\Providers;

use App\Events\LabRequestCreated;
use App\Events\LabResultsCompleted;
use App\Events\PatientAdmitted;
use App\Events\PatientDischarged;
use App\Events\PaymentRecorded;
use App\Events\PrescriptionCreated;
use App\Events\StockLow;
use App\Listeners\NotifyAccountants;
use App\Listeners\NotifyAccountantsDischarge;
use App\Listeners\NotifyDoctorLabResults;
use App\Listeners\NotifyLabTechnicians;
use App\Listeners\NotifyPharmacists;
use App\Listeners\NotifyStockManagers;
use App\Listeners\NotifyWardStaffAdmission;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Super Admin bypasses all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        // Register event listeners for notifications
        Event::listen(LabRequestCreated::class, NotifyLabTechnicians::class);
        Event::listen(LabResultsCompleted::class, NotifyDoctorLabResults::class);
        Event::listen(PrescriptionCreated::class, NotifyPharmacists::class);
        Event::listen(PaymentRecorded::class, NotifyAccountants::class);
        Event::listen(PatientAdmitted::class, NotifyWardStaffAdmission::class);
        Event::listen(PatientDischarged::class, NotifyAccountantsDischarge::class);
        Event::listen(StockLow::class, NotifyStockManagers::class);
    }
}

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
use App\Models\User;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleService::class);
        $this->app->singleton(SidebarMenuBuilder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Paginator::defaultView('vendor.pagination.uhms');
        Paginator::defaultSimpleView('vendor.pagination.uhms-simple');

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

        // ---- Module feature-flag Blade directives ----
        // @module('pharmacy') ... @endmodule  → renders only when module enabled
        Blade::if('module', function (string $slug) {
            return app(ModuleService::class)->enabled($slug);
        });

        View::composer('layouts.partials.sidebar', function ($view) {
            /** @var User|null $user */
            $user = Auth::user();
            $moduleService = app(ModuleService::class);

            $unreadNotifications = $user instanceof User
                && $moduleService->enabled('notifications')
                && $user->can('notifications.view')
                    ? $user->unreadNotifications()->count()
                    : 0;

            $view->with('sidebarSections', app(SidebarMenuBuilder::class)->build(
                $user,
                request()->route()?->getName() ?? '',
                $unreadNotifications,
            ));
        });
    }
}

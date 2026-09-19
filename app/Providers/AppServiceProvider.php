<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Policies\ApplicationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // ApplicationModel doesn't follow the Model/ModelPolicy naming convention
        // ("Application" is reserved by Illuminate\Foundation\Application), so it
        // needs an explicit policy mapping.
        Gate::policy(ApplicationModel::class, ApplicationPolicy::class);

        // Queue counts shown as badges in the admin sidebar.
        View::composer('components.layouts.admin', function ($view) {
            $view->with([
                'pendingApplications' => ApplicationModel::whereIn('status', ['submitted', 'under_review'])->count(),
                'pendingActivities' => Activity::where('status', 'pending')->count(),
            ]);
        });
    }
}

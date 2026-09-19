<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AnnualReportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Cso;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/accreditation', [PublicController::class, 'accreditation'])->name('accreditation');
Route::get('/accredited-csos', [PublicController::class, 'directory'])->name('directory');
Route::get('/accredited-csos/{organization}', [PublicController::class, 'organization'])->name('directory.show');
Route::get('/resources', [PublicController::class, 'resources'])->name('resources');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');
Route::get('/annual-reports/{annualReport}/download', [AnnualReportController::class, 'download'])
    ->name('annual-reports.download');

// Post-login landing: send each role to its own dashboard rather than a shared one.
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'cso.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Private uploads are never served from a guessable path; the policy check lives here.
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

    Route::get('/applications', [Admin\ApplicationReviewController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [Admin\ApplicationReviewController::class, 'show'])->name('applications.show');
    Route::patch('/applications/{application}/stage', [Admin\ApplicationReviewController::class, 'updateStage'])->name('applications.stage');
    Route::post('/applications/{application}/approve', [Admin\ApplicationReviewController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [Admin\ApplicationReviewController::class, 'reject'])->name('applications.reject');

    Route::get('/activities', [Admin\ActivityVerificationController::class, 'index'])->name('activities.index');
    Route::post('/activities/{activity}/verify', [Admin\ActivityVerificationController::class, 'verify'])->name('activities.verify');
    Route::post('/activities/{activity}/reject', [Admin\ActivityVerificationController::class, 'reject'])->name('activities.reject');

    Route::get('/organizations', [Admin\OrganizationModerationController::class, 'index'])->name('organizations.index');
    Route::patch('/organizations/{organization}/visibility', [Admin\OrganizationModerationController::class, 'toggleVisibility'])->name('organizations.visibility');

    Route::get('/scorecards', [Admin\ScorecardController::class, 'index'])->name('scorecards.index');
    Route::post('/scorecards/recalculate', [Admin\ScorecardController::class, 'recalculate'])->name('scorecards.recalculate');

    Route::get('/analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [Admin\AnalyticsController::class, 'export'])->name('analytics.export');

    Route::resource('news', Admin\NewsPostController::class)
        ->parameters(['news' => 'news_post'])
        ->except('show');

    Route::get('/annual-reports', [Admin\AnnualReportAdminController::class, 'index'])->name('annual-reports.index');
    Route::post('/annual-reports', [Admin\AnnualReportAdminController::class, 'store'])->name('annual-reports.store');
    Route::put('/annual-reports/{annual_report}', [Admin\AnnualReportAdminController::class, 'update'])->name('annual-reports.update');
    Route::delete('/annual-reports/{annual_report}', [Admin\AnnualReportAdminController::class, 'destroy'])->name('annual-reports.destroy');
});

Route::middleware(['auth', 'verified', 'role:cso_rep'])->prefix('cso')->name('cso.')->group(function () {
    Route::get('/dashboard', Cso\DashboardController::class)->name('dashboard');

    Route::get('/profile', [Cso\OrganizationProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [Cso\OrganizationProfileController::class, 'update'])->name('profile.update');

    Route::get('/applications', [Cso\ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/create', [Cso\ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [Cso\ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications/{application}', [Cso\ApplicationController::class, 'show'])->name('applications.show');

    Route::get('/activities', [Cso\ActivityController::class, 'index'])->name('activities.index');
    Route::post('/activities', [Cso\ActivityController::class, 'store'])->name('activities.store');
});

require __DIR__.'/auth.php';

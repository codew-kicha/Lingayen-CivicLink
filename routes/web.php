<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AnnualReportController;
use App\Http\Controllers\Auth;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Cso;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Services\ApplicationSteps;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/accreditation', [PublicController::class, 'accreditation'])->name('accreditation');
Route::get('/accredited-csos', [PublicController::class, 'directory'])->name('directory');
Route::get('/accredited-csos/{organization}', [PublicController::class, 'organization'])->name('directory.show');
Route::get('/resources', [PublicController::class, 'resources'])->name('resources');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::view('/privacy', 'public.privacy')->name('privacy');
// Each throttle names its own counter (third argument): unnamed limits share one counter per IP,
// so downloads could exhaust the contact form for everyone on an office network.
Route::post('/contact', [ContactController::class, 'send'])
    ->middleware('throttle:5,1,contact')->name('contact.send');
Route::get('/annual-reports/{annualReport}/download', [AnnualReportController::class, 'download'])
    ->name('annual-reports.download');

// Admin-sent invitation: signed, expiring, and single-use (see InvitationController).
Route::middleware(['signed', 'throttle:10,1,invitation'])->group(function () {
    Route::get('/invitation/{user}', [Auth\InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitation/{user}', [Auth\InvitationController::class, 'store'])->name('invitation.store');
});

// Post-login landing: send each role to its own dashboard rather than a shared one.
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'cso.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Private uploads are never served from a guessable path; the policy check lives here.
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::get('/activities/{activity}/evidence', [DocumentController::class, 'evidence'])
        ->name('activities.evidence');
    Route::get('/activities/{activity}/photos/{photo}', [DocumentController::class, 'photo'])
        ->scopeBindings()->name('activities.photo');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/activities/verify-consistent', [Admin\ActivityVerificationController::class, 'verifyConsistent'])->name('activities.verify-consistent');
    Route::post('/activities/read-sheets', [Admin\ActivityVerificationController::class, 'readSheets'])->middleware('throttle:10,1,read-sheets')->name('activities.read-sheets');
    Route::get('/notices', [Admin\NoticeController::class, 'index'])->name('notices.index');
    Route::get('/notices/count', [Admin\NoticeController::class, 'count'])->name('notices.count');
    Route::post('/notices', [Admin\NoticeController::class, 'store'])->middleware('throttle:20,1,notices')->name('notices.store');
    // The Sangguniang Bayan's blank paper forms (resources/forms), printed for walk-in applicants.
    // Applicants fill the form in online instead (pre-final defense panel).
    Route::get('/forms/{type}', [PublicController::class, 'applicationForm'])
        ->whereIn('type', ['new', 'renewal'])->name('accreditation.form');
    Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

    Route::get('/applications', [Admin\ApplicationReviewController::class, 'index'])->name('applications.index');
    Route::get('/applications/export', [Admin\ApplicationReviewController::class, 'export'])->name('applications.export');
    Route::get('/applications/{application}', [Admin\ApplicationReviewController::class, 'show'])->name('applications.show');
    Route::post('/applications/{application}/forward', [Admin\ApplicationReviewController::class, 'forwardToCommittee'])->name('applications.forward');
    Route::post('/applications/{application}/hearings', [Admin\ApplicationReviewController::class, 'recordHearing'])->name('applications.hearing');
    Route::delete('/applications/{application}/hearings/latest', [Admin\ApplicationReviewController::class, 'undoHearing'])->name('applications.hearing.undo');
    Route::post('/applications/{application}/approve-form', [Admin\ApplicationReviewController::class, 'approveForm'])->name('applications.approve-form');
    Route::patch('/applications/{application}/fee', [Admin\ApplicationReviewController::class, 'updateFee'])->name('applications.fee');
    Route::post('/applications/{application}/return-requirements', [Admin\ApplicationReviewController::class, 'returnRequirements'])->name('applications.return-requirements');
    Route::post('/applications/{application}/approve', [Admin\ApplicationReviewController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [Admin\ApplicationReviewController::class, 'reject'])->name('applications.reject');

    // Free filing periods: no fee, and three-year accreditation, for applications submitted inside one.
    Route::get('/fee-periods', [Admin\FeeFreePeriodController::class, 'index'])->name('fee-periods.index');
    Route::post('/fee-periods', [Admin\FeeFreePeriodController::class, 'store'])->name('fee-periods.store');
    Route::put('/fee-periods/{period}', [Admin\FeeFreePeriodController::class, 'update'])->name('fee-periods.update');
    Route::delete('/fee-periods/{period}', [Admin\FeeFreePeriodController::class, 'destroy'])->name('fee-periods.destroy');

    // LGU events: organized by the LGU, recorded and announced by the office, attendance credited to CSOs.
    Route::get('/lgu-events', [Admin\LguEventController::class, 'index'])->name('lgu-events.index');
    Route::post('/lgu-events', [Admin\LguEventController::class, 'store'])->name('lgu-events.store');
    Route::get('/lgu-events/{event}', [Admin\LguEventController::class, 'show'])->name('lgu-events.show');
    Route::put('/lgu-events/{event}', [Admin\LguEventController::class, 'update'])->name('lgu-events.update');
    Route::post('/lgu-events/{event}/attendance', [Admin\LguEventController::class, 'attendance'])->name('lgu-events.attendance');
    Route::delete('/lgu-events/{event}', [Admin\LguEventController::class, 'destroy'])->name('lgu-events.destroy');

    Route::get('/activities', [Admin\ActivityVerificationController::class, 'index'])->name('activities.index');
    Route::post('/activities/{activity}/verify', [Admin\ActivityVerificationController::class, 'verify'])->name('activities.verify');
    Route::post('/activities/{activity}/reject', [Admin\ActivityVerificationController::class, 'reject'])->name('activities.reject');
    Route::post('/activities/{activity}/link-event', [Admin\ActivityVerificationController::class, 'linkToEvent'])->name('activities.link-event');

    Route::get('/organizations', [Admin\OrganizationModerationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [Admin\OrganizationModerationController::class, 'create'])->name('organizations.create');
    Route::get('/organizations/export', [Admin\OrganizationModerationController::class, 'export'])->name('organizations.export');
    Route::get('/organizations/import', [Admin\OrganizationImportController::class, 'create'])->name('organizations.import');
    Route::post('/organizations/import', [Admin\OrganizationImportController::class, 'store'])->name('organizations.import.store');
    Route::get('/organizations/import/template', [Admin\OrganizationImportController::class, 'template'])->name('organizations.import.template');
    Route::post('/organizations', [Admin\OrganizationModerationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}/edit', [Admin\OrganizationModerationController::class, 'edit'])->name('organizations.edit');
    Route::patch('/organizations/{organization}', [Admin\OrganizationModerationController::class, 'update'])->name('organizations.update');
    Route::get('/organizations/{organization}/members.pdf', [Admin\OrganizationModerationController::class, 'members'])->name('organizations.members');
    Route::post('/organizations/{organization}/account', [Admin\OrganizationModerationController::class, 'attachAccount'])->name('organizations.account');
    Route::patch('/organizations/{organization}/visibility', [Admin\OrganizationModerationController::class, 'toggleVisibility'])->name('organizations.visibility');
    Route::post('/organizations/{organization}/remind', [Admin\OrganizationModerationController::class, 'remind'])->name('organizations.remind');
    Route::patch('/organizations/{organization}/accreditation-expiry', [Admin\OrganizationModerationController::class, 'updateExpiry'])->name('organizations.expiry');
    // Both re-check the admin's password inside the dialog (ConfirmedOrganizationRequest).
    Route::delete('/organizations/{organization}', [Admin\OrganizationModerationController::class, 'destroy'])->name('organizations.destroy');
    Route::post('/organizations/{organization}/merge', [Admin\OrganizationModerationController::class, 'merge'])->name('organizations.merge');

    // Assisted encoding: PESO files paper submissions on an organization's behalf.
    Route::get('/organizations/{organization}/applications/create', [Admin\AssistedEncodingController::class, 'createApplication'])->name('organizations.applications.create');
    Route::post('/organizations/{organization}/applications', [Admin\AssistedEncodingController::class, 'storeApplication'])->name('organizations.applications.store');
    Route::get('/organizations/{organization}/activities/create', [Admin\AssistedEncodingController::class, 'createActivity'])->name('organizations.activities.create');
    Route::post('/organizations/{organization}/activities', [Admin\AssistedEncodingController::class, 'storeActivity'])->name('organizations.activities.store');

    Route::get('/accounts', [Admin\AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts/{user}/invitation', [Admin\AccountController::class, 'resendInvitation'])->name('accounts.invitation');
    Route::patch('/accounts/{user}/reactivate', [Admin\AccountController::class, 'reactivate'])->name('accounts.reactivate');
    // Re-checks the admin's own password inside the form (UpdateCredentialsRequest), so it works from a dialog.
    Route::patch('/accounts/{user}', [Admin\AccountController::class, 'update'])->name('accounts.update');
    Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/system-check', Admin\SystemCheckController::class)->name('system-check');

    // Actions that grant access or remove it: the admin re-enters their password first.
    Route::middleware('password.confirm')->group(function () {
        Route::get('/accounts/create', [Admin\AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [Admin\AccountController::class, 'store'])->name('accounts.store');
        Route::patch('/accounts/{user}/deactivate', [Admin\AccountController::class, 'deactivate'])->name('accounts.deactivate');
        Route::post('/organizations/{organization}/revoke', [Admin\OrganizationModerationController::class, 'revoke'])->name('organizations.revoke');
    });

    Route::get('/scorecards', [Admin\ScorecardController::class, 'index'])->name('scorecards.index');
    Route::post('/scorecards/recalculate', [Admin\ScorecardController::class, 'recalculate'])->name('scorecards.recalculate');

    Route::get('/analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [Admin\AnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('/analytics/excel', [Admin\AnalyticsController::class, 'excel'])->name('analytics.excel');
    Route::get('/analytics/awards', [Admin\AnalyticsController::class, 'awards'])->name('analytics.awards');

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
    Route::get('/notifications', [Cso\NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/profile', [Cso\OrganizationProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [Cso\OrganizationProfileController::class, 'update'])->name('profile.update');
    Route::get('/members/template', [Cso\MemberListController::class, 'template'])->name('members.template');
    Route::post('/members/import', [Cso\MemberListController::class, 'import'])->middleware('throttle:20,1,member-import')->name('members.import');

    Route::get('/applications', [Cso\ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/create', [Cso\ApplicationController::class, 'create'])->name('applications.create');

    // Applying: members, the form filled in online, the other requirements, review and submit.
    Route::get('/application', [Cso\ApplicationWizardController::class, 'resume'])->name('application');
    Route::get('/application/preview', [Cso\ApplicationWizardController::class, 'preview'])->name('application.preview');
    Route::get('/application/requirements', [Cso\ApplicationWizardController::class, 'requirements'])->name('application.requirements.show');
    Route::get('/application/{step}', [Cso\ApplicationWizardController::class, 'show'])
        ->whereIn('step', ApplicationSteps::PAGES)->name('application.step');
    Route::post('/application/members', [Cso\ApplicationWizardController::class, 'saveMembers'])->name('application.members');
    Route::post('/application/form', [Cso\ApplicationWizardController::class, 'saveForm'])->name('application.form');
    // Uploads up to 5 MB each: limited so one account can't fill the disk.
    Route::post('/application/requirements', [Cso\ApplicationWizardController::class, 'saveRequirements'])
        ->middleware('throttle:30,1,application-uploads')->name('application.requirements');
    Route::delete('/application/documents/{document}', [Cso\ApplicationWizardController::class, 'removeDocument'])->name('application.documents.destroy');
    Route::post('/application/submit', [Cso\ApplicationWizardController::class, 'submit'])->name('application.submit');
    Route::get('/applications/{application}', [Cso\ApplicationController::class, 'show'])->name('applications.show');

    Route::get('/activities', [Cso\ActivityController::class, 'index'])->name('activities.index');
    Route::post('/activities', [Cso\ActivityController::class, 'store'])->name('activities.store');
    Route::get('/activities/{activity}/edit', [Cso\ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('/activities/{activity}', [Cso\ActivityController::class, 'update'])->name('activities.update');
    Route::delete('/activities/{activity}', [Cso\ActivityController::class, 'destroy'])->name('activities.destroy');
});

require __DIR__.'/auth.php';

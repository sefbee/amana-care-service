<?php

use App\Admin\Controllers\AdminConsole\MenuController;
use App\Admin\Controllers\AdminConsole\PermissionController;
use App\Admin\Controllers\AdminConsole\UserRoleController;
use App\Admin\Controllers\DashboardController;
use App\Admin\Controllers\ExecutiveReportController;
use App\Admin\Controllers\AuditLogController;
use App\Admin\Controllers\OperationalNotificationController;
use App\Admin\Controllers\FinanceInvoiceController;
use App\Admin\Controllers\FinancePaymentController;
use App\Admin\Controllers\RoutineComplaintController;
use App\Admin\Controllers\CaregiverController;
use App\Admin\Controllers\TrainingRecordController;
use App\Admin\Controllers\CaregiverReadinessAlertController;
use App\Admin\Controllers\HouseholdController;
use App\Admin\Controllers\PriceVersionController;
use App\Admin\Controllers\ServiceController;
use App\Admin\Controllers\ServiceRequestController;
use App\Admin\Controllers\QuoteController;
use App\Admin\Controllers\MatchingController;
use App\Admin\Controllers\ProposalController;
use App\Admin\Controllers\PlacementController;
use App\Admin\Controllers\RecordConflictBlockController;
use App\Admin\Controllers\PlacementFollowUpController;
use App\Admin\Controllers\SafeguardingIncidentController;
use App\Admin\Controllers\Household\CareRecipientController;
use App\Admin\Controllers\Household\HouseholdContactController;
use App\Admin\Controllers\Lead\CommunicationLogController;
use App\Admin\Controllers\Lead\ConsentController;
use App\Admin\Controllers\LeadController;
use App\Admin\Controllers\UserController;
use Illuminate\Support\Facades\Route;
 


Route::middleware(['auth', 'active_user'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('notifications', [OperationalNotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [OperationalNotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('admin-console/notification-operations', [OperationalNotificationController::class, 'operations'])
        ->middleware('platform_superuser_access')->name('notifications.operations');

    Route::middleware('role_permission_check')->group(function () {
        Route::get('system-audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('executive-reports', [ExecutiveReportController::class, 'index'])->name('executive.reports.index');
        Route::get('executive-reports/export', [ExecutiveReportController::class, 'export'])->name('executive.reports.export');
        Route::resource('users', UserController::class);
        Route::post('users/update-status/{id}', [UserController::class, 'updateStatus'])->name('users.update-status');
        Route::post('users/{user}/password-reset', [UserController::class, 'passwordReset'])->name('users.password-reset');
        Route::get('users/role-wise-users/{role_slug}/{supervisor_user_code?}', [UserController::class, 'role_wise_users'])->name('users.role-wise-users');
        Route::post('users-bulk-upload', [UserController::class, 'bulkUpload'])->name('users.bulk-upload');

        // Service Catalog & Pricing
        Route::resource('services', ServiceController::class);
        Route::post('services/{service}/update-status', [ServiceController::class, 'updateStatus'])->name('services.update-status');
        Route::post('services/{service}/price-versions', [PriceVersionController::class, 'store'])->name('services.price-versions.store');
        Route::post('services/{service}/price-versions/{price}/approve', [PriceVersionController::class, 'approve'])->name('services.price-versions.approve');
        Route::post('services/{service}/price-versions/{price}/retire', [PriceVersionController::class, 'retire'])->name('services.price-versions.retire');
        Route::delete('services/{service}/price-versions/{price}', [PriceVersionController::class, 'destroy'])->name('services.price-versions.destroy');

        Route::get('caregivers/import', [CaregiverController::class, 'importForm'])->name('caregivers.import.form');
        Route::post('caregivers/import', [CaregiverController::class, 'import'])->name('caregivers.import.store');
        Route::get('caregivers/import-template', [CaregiverController::class, 'importTemplate'])->name('caregivers.import.template');
        Route::resource('caregivers', CaregiverController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('caregivers/{caregiver}/checks', [CaregiverController::class, 'recordCheck'])->name('caregivers.checks.store');
        Route::post('caregivers/{caregiver}/checks/{check}/approve', [CaregiverController::class, 'approveScreeningCheck'])->name('caregivers.checks.approve');
        Route::post('caregivers/{caregiver}/readiness', [CaregiverController::class, 'approveReadiness'])->name('caregivers.readiness.store');
        Route::post('caregivers/{caregiver}/documents', [CaregiverController::class, 'storeDocument'])->name('caregivers.documents.store');
        Route::get('caregiver-readiness-alerts', [CaregiverReadinessAlertController::class, 'index'])->name('caregiver-readiness-alerts.index');
        Route::post('caregiver-readiness-alerts/{alert}/acknowledge', [CaregiverReadinessAlertController::class, 'acknowledge'])->name('caregiver-readiness-alerts.read');
        Route::get('training-records', [TrainingRecordController::class, 'index'])->name('training-records.index');
        Route::post('training-records/{caregiver}', [TrainingRecordController::class, 'store'])->name('training-records.store');
        Route::get('placement-follow-ups', [PlacementFollowUpController::class, 'index'])->name('placement-follow-ups.index');
        Route::get('placement-follow-ups/{task}', [PlacementFollowUpController::class, 'show'])->name('placement-follow-ups.show');
        Route::post('placement-follow-ups/{task}/attempts', [PlacementFollowUpController::class, 'recordAttempt'])->name('placement-follow-ups.attempts.store');
        Route::get('routine-complaints', [RoutineComplaintController::class, 'index'])->name('routine-complaints.index');
        Route::get('routine-complaints/create', [RoutineComplaintController::class, 'create'])->name('routine-complaints.create');
        Route::post('routine-complaints', [RoutineComplaintController::class, 'store'])->name('routine-complaints.store');
        Route::get('routine-complaints/{complaint}', [RoutineComplaintController::class, 'show'])->name('routine-complaints.show');
        Route::patch('routine-complaints/{complaint}', [RoutineComplaintController::class, 'update'])->name('routine-complaints.update');

        Route::get('record-conflict-blocks', [RecordConflictBlockController::class, 'index'])->name('record-conflict-blocks.index');
        Route::get('record-conflict-blocks/create', [RecordConflictBlockController::class, 'create'])->name('record-conflict-blocks.create');
        Route::post('record-conflict-blocks', [RecordConflictBlockController::class, 'store'])->name('record-conflict-blocks.store');
        Route::post('record-conflict-blocks/{block}/revoke', [RecordConflictBlockController::class, 'revoke'])->name('record-conflict-blocks.revoke');

        Route::prefix('finance')->group(function () {
            Route::get('invoices', [FinanceInvoiceController::class, 'index'])->name('finance.invoices.index');
            Route::get('invoices/{invoice}', [FinanceInvoiceController::class, 'show'])->name('finance.invoices.show');
            Route::post('invoices/{invoice}/issue', [FinanceInvoiceController::class, 'issue'])->name('finance.invoices.issue');
            Route::get('payments', [FinancePaymentController::class, 'index'])->name('finance.payments.index');
            Route::get('payments/create', [FinancePaymentController::class, 'create'])->name('finance.payments.create');
            Route::post('payments', [FinancePaymentController::class, 'store'])->name('finance.payments.store');
            Route::post('payments/{payment}/reconcile', [FinancePaymentController::class, 'reconcile'])->name('finance.payments.reconcile');
            Route::get('payments/{payment}/receipt', [FinancePaymentController::class, 'receipt'])->name('finance.payments.receipt');
        });
        Route::get('safeguarding/incidents', [SafeguardingIncidentController::class, 'index'])->withoutMiddleware('role_permission_check')->name('safeguarding.incidents.index');
        Route::get('safeguarding/incidents/create', [SafeguardingIncidentController::class, 'create'])->withoutMiddleware('role_permission_check')->name('safeguarding.incidents.create');
        Route::post('safeguarding/incidents', [SafeguardingIncidentController::class, 'store'])->withoutMiddleware('role_permission_check')->name('safeguarding.incidents.store');
        Route::post('safeguarding/incidents/alerts/{alert}/acknowledge', [SafeguardingIncidentController::class, 'acknowledge'])->withoutMiddleware('role_permission_check')->name('safeguarding.incidents.alerts.acknowledge');
        Route::post('safeguarding/incidents/{incident}/caregiver-hold', [SafeguardingIncidentController::class, 'placeCaregiverOnHold'])->withoutMiddleware('role_permission_check')->name('safeguarding.incidents.caregiver-hold');
        Route::get('safeguarding/incidents/{incident}', [SafeguardingIncidentController::class, 'show'])->withoutMiddleware('role_permission_check')->name('safeguarding.incidents.show');
        Route::post('caregiver-documents/{document}/reveal', [CaregiverController::class, 'revealDocument'])
            ->withoutMiddleware('role_permission_check')->name('caregivers.documents.reveal');

        // CRM routes use a consistent URL prefix; named routes remain stable for Blade links.
        Route::prefix('crm')->group(function () {
            Route::resource('leads', LeadController::class);
            Route::post('leads/{lead}/update-status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
            Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
            Route::post('leads/{lead}/communication-logs', [CommunicationLogController::class, 'store'])->name('leads.communication-logs.store');
            Route::delete('leads/{lead}/communication-logs/{log}', [CommunicationLogController::class, 'destroy'])->name('leads.communication-logs.destroy');
            Route::post('leads/{lead}/consents', [ConsentController::class, 'store'])->name('leads.consents.store');
            Route::put('leads/{lead}/consents/{consent}', [ConsentController::class, 'update'])->name('leads.consents.update');

            Route::resource('households', HouseholdController::class);
            Route::post('households/{household}/service-requests', [ServiceRequestController::class, 'store'])->name('households.service-requests.store');
            Route::post('service-requests/{serviceRequest}/quotes', [QuoteController::class, 'store'])->name('service-requests.quotes.store');
            Route::post('quotes/{quote}/approve', [QuoteController::class, 'approve'])->name('quotes.approve');
            Route::get('service-requests/{serviceRequest}/candidates', [MatchingController::class, 'candidates'])->name('service-requests.candidates');
            Route::post('service-requests/{serviceRequest}/proposals', [ProposalController::class, 'store'])->name('service-requests.proposals.store');
            Route::post('proposal-candidates/{candidate}/family-decision', [ProposalController::class, 'recordFamilyDecision'])->name('proposal-candidates.family-decision');
            Route::post('proposal-candidates/{candidate}/caregiver-response', [ProposalController::class, 'recordCaregiverResponse'])->name('proposal-candidates.caregiver-response');
            Route::get('proposals/{proposal}/family-summary', [PlacementController::class, 'familySummary'])->name('proposals.family-summary');
            Route::post('proposal-candidates/{candidate}/activate-placement', [PlacementController::class, 'activate'])->name('proposal-candidates.placement.activate');
            Route::post('placements/{placement}/transition', [PlacementController::class, 'transition'])->name('placements.transition');
            Route::post('placements/{placement}/agreement/reveal', [PlacementController::class, 'revealAgreement'])->name('placements.agreement.reveal');
            Route::post('households/{household}/update-status', [HouseholdController::class, 'updateStatus'])->name('households.update-status');
            Route::post('households/{household}/contacts', [HouseholdContactController::class, 'store'])->name('households.contacts.store');
            Route::put('households/{household}/contacts/{contact}', [HouseholdContactController::class, 'update'])->name('households.contacts.update');
            Route::delete('households/{household}/contacts/{contact}', [HouseholdContactController::class, 'destroy'])->name('households.contacts.destroy');
            Route::post('households/{household}/care-recipients', [CareRecipientController::class, 'store'])->name('households.care-recipients.store');
            Route::put('households/{household}/care-recipients/{recipient}', [CareRecipientController::class, 'update'])->name('households.care-recipients.update');
            Route::delete('households/{household}/care-recipients/{recipient}', [CareRecipientController::class, 'destroy'])->name('households.care-recipients.destroy');
        });



        Route::prefix('admin-console')
            ->middleware(['admin_console_access'])
            ->group(function () {
                Route::resource('menus', MenuController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::get('system-info', [UserRoleController::class, 'system_info'])->name('system-info');

                Route::middleware('platform_superuser_access')->group(function () {
                    Route::delete('permissions/{permission}/destroy-not-exists', [PermissionController::class, 'destroy_not_exists'])->name('permissions.destroy-not-exists');
                    Route::resource('permissions', PermissionController::class)->only(['index', 'store', 'update', 'destroy']);
                    Route::post('sync-permissions', [PermissionController::class, 'syncPermissions'])->name('permissions.sync');
                    Route::post('sync-controller-permissions/{permission}', [PermissionController::class, 'sync_controller_permissions'])->name('permissions.sync-controller-permissions');
                    Route::resource('user-roles', UserRoleController::class)->only(['index', 'store', 'update', 'destroy']);
                    Route::get('user-roles/{user_role}/config', [UserRoleController::class, 'config'])->name('user-roles.config');
                    Route::put('user-roles/{user_role}/update-menus', [UserRoleController::class, 'updateMenus'])->name('user-roles.update-menus');
                    Route::put('user-roles/{user_role}/update-permissions', [UserRoleController::class, 'updatePermissions'])->name('user-roles.update-permissions');
                });
            });
        
    });
});


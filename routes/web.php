<?php

use App\Http\Controllers\AdminConsole\MenuController;
use App\Http\Controllers\AdminConsole\PermissionController;
use App\Http\Controllers\AdminConsole\UserRoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\PriceVersionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Household\CareRecipientController;
use App\Http\Controllers\Household\HouseholdContactController;
use App\Http\Controllers\Lead\CommunicationLogController;
use App\Http\Controllers\Lead\ConsentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
 

Route::redirect('/', 'dashboard');
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role_permission_check')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/update-status/{id}', [UserController::class, 'updateStatus'])->name('users.update-status');
        Route::post('users/reset-password/{id}', [UserController::class, 'resetPassword'])->name('users.reset-password');  
        Route::get('users/{user}/password-reset', [UserController::class, 'passwordReset'])->name('users.password-reset');
        Route::get('users/role-wise-users/{role_slug}/{supervisor_user_code?}', [UserController::class, 'role_wise_users'])->name('users.role-wise-users');
        Route::post('users-bulk-upload', [UserController::class, 'bulkUpload'])->name('users.bulk-upload');

        // Service Catalog & Pricing
        Route::resource('services', ServiceController::class);
        Route::post('services/{service}/update-status', [ServiceController::class, 'updateStatus'])->name('services.update-status');
        Route::post('services/{service}/price-versions', [PriceVersionController::class, 'store'])->name('services.price-versions.store');
        Route::post('services/{service}/price-versions/{price}/approve', [PriceVersionController::class, 'approve'])->name('services.price-versions.approve');
        Route::post('services/{service}/price-versions/{price}/retire', [PriceVersionController::class, 'retire'])->name('services.price-versions.retire');
        Route::delete('services/{service}/price-versions/{price}', [PriceVersionController::class, 'destroy'])->name('services.price-versions.destroy');

        // CRM — Leads
        Route::resource('leads', LeadController::class);
        Route::post('leads/{lead}/update-status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
        Route::post('leads/{lead}/communication-logs', [CommunicationLogController::class, 'store'])->name('leads.communication-logs.store');
        Route::delete('leads/{lead}/communication-logs/{log}', [CommunicationLogController::class, 'destroy'])->name('leads.communication-logs.destroy');
        Route::post('leads/{lead}/consents', [ConsentController::class, 'store'])->name('leads.consents.store');
        Route::put('leads/{lead}/consents/{consent}', [ConsentController::class, 'update'])->name('leads.consents.update');

        // CRM — Households
        Route::resource('households', HouseholdController::class);
        Route::post('households/{household}/update-status', [HouseholdController::class, 'updateStatus'])->name('households.update-status');
        Route::post('households/{household}/contacts', [HouseholdContactController::class, 'store'])->name('households.contacts.store');
        Route::put('households/{household}/contacts/{contact}', [HouseholdContactController::class, 'update'])->name('households.contacts.update');
        Route::delete('households/{household}/contacts/{contact}', [HouseholdContactController::class, 'destroy'])->name('households.contacts.destroy');
        Route::post('households/{household}/care-recipients', [CareRecipientController::class, 'store'])->name('households.care-recipients.store');
        Route::put('households/{household}/care-recipients/{recipient}', [CareRecipientController::class, 'update'])->name('households.care-recipients.update');
        Route::delete('households/{household}/care-recipients/{recipient}', [CareRecipientController::class, 'destroy'])->name('households.care-recipients.destroy');

        Route::prefix('admin-console')
            ->middleware(['admin_console_access'])
            ->group(function () {
                Route::resource('menus', MenuController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::delete('permissions/{permission}/destroy-not-exists', [PermissionController::class, 'destroy_not_exists'])->name('permissions.destroy-not-exists');
                Route::resource('permissions', PermissionController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::get('sync-permissions', [PermissionController::class, 'syncPermissions'])->name('permissions.sync');
                Route::get('sync-controller-permissions/{permission}', [PermissionController::class, 'sync_controller_permissions'])->name('permissions.sync-controller-permissions');
                Route::resource('user-roles', UserRoleController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::get('user-roles/{user_role}/config', [UserRoleController::class, 'config'])->name('user-roles.config');
                Route::put('user-roles/{user_role}/update-menus', [UserRoleController::class, 'updateMenus'])->name('user-roles.update-menus');
                Route::put('user-roles/{user_role}/update-permissions', [UserRoleController::class, 'updatePermissions'])->name('user-roles.update-permissions');
                Route::get('system-info', [UserRoleController::class, 'system_info'])->name('system-info');
            });
        
    });
});

require __DIR__ . '/auth.php';

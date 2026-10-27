<?php

use App\FrontEnd\Cms\Controllers\CmsPublicController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/cms')->name('api.v1.cms.')->group(function () {
    Route::get('pages', [CmsPublicController::class, 'pages'])->name('pages.index');
    Route::get('pages/{slug}', [CmsPublicController::class, 'page'])->name('pages.show');
    Route::get('navigation/{location}', [CmsPublicController::class, 'navigation'])->whereIn('location', ['header', 'footer'])->name('navigation.show');
    Route::get('settings', [CmsPublicController::class, 'settings'])->name('settings');
    Route::post('leads', [CmsPublicController::class, 'submitLead'])->middleware('throttle:5,1')->name('leads.store');
});

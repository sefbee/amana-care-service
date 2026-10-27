<?php

use App\FrontEnd\Cms\Controllers\CmsMediaController;
use App\FrontEnd\Cms\Controllers\CmsMenuController;
use App\FrontEnd\Cms\Controllers\CmsPageController;
use App\FrontEnd\Cms\Controllers\CmsSectionController;
use App\FrontEnd\Cms\Controllers\CmsSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active_user', 'role_permission_check'])->group(function () {
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::get('pages', [CmsPageController::class, 'index'])->name('pages.index');
            Route::get('pages/create', [CmsPageController::class, 'create'])->name('pages.create');
            Route::post('pages', [CmsPageController::class, 'store'])->name('pages.store');
            Route::get('pages/{page}/edit', [CmsPageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page}', [CmsPageController::class, 'update'])->name('pages.update');
            Route::delete('pages/{page}', [CmsPageController::class, 'destroy'])->name('pages.destroy');
            Route::get('pages/{page}/preview', [CmsPageController::class, 'preview'])->name('pages.preview');
            Route::post('pages/{page}/publish', [CmsPageController::class, 'publish'])->name('pages.publish');
            Route::post('pages/{page}/unpublish', [CmsPageController::class, 'unpublish'])->name('pages.unpublish');

            Route::post('pages/{page}/sections', [CmsSectionController::class, 'store'])->name('sections.store');
            Route::put('pages/{page}/sections/{section}', [CmsSectionController::class, 'update'])->name('sections.update');
            Route::delete('pages/{page}/sections/{section}', [CmsSectionController::class, 'destroy'])->name('sections.destroy');
            Route::post('pages/{page}/sections/{section}/reorder', [CmsSectionController::class, 'reorder'])->name('sections.reorder');

            Route::get('media', [CmsMediaController::class, 'index'])->name('media.index');
            Route::post('media', [CmsMediaController::class, 'store'])->name('media.store');
            Route::put('media/{media}', [CmsMediaController::class, 'update'])->name('media.update');
            Route::delete('media/{media}', [CmsMediaController::class, 'destroy'])->name('media.destroy');

            Route::get('navigation', [CmsMenuController::class, 'index'])->name('navigation.index');
            Route::post('navigation', [CmsMenuController::class, 'store'])->name('navigation.store');
            Route::put('navigation/{item}', [CmsMenuController::class, 'update'])->name('navigation.update');
            Route::delete('navigation/{item}', [CmsMenuController::class, 'destroy'])->name('navigation.destroy');
            Route::post('navigation/{item}/reorder', [CmsMenuController::class, 'reorder'])->name('navigation.reorder');

            Route::get('settings', [CmsSettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [CmsSettingController::class, 'update'])->name('settings.update');
            Route::post('settings/publish', [CmsSettingController::class, 'publish'])->name('settings.publish');
        });
});

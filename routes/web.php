<?php

use App\Http\Controllers\Api\Admin\AdminPageController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\BookingSettingsController;
use App\Http\Controllers\Api\Admin\InquiryController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingInquiryController;
use App\Http\Controllers\Api\PublicPageController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/page-sitemap.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap.index');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::prefix('api')->group(function () {
    Route::get('/site', [SiteController::class, 'show']);
    Route::get('/pages', [PublicPageController::class, 'index']);
    Route::get('/page', [PublicPageController::class, 'show']);
    Route::get('/booking/config', [BookingController::class, 'config']);
    Route::get('/booking/addresses', [BookingController::class, 'addresses'])
        ->middleware('throttle:30,1');
    Route::post('/booking/quote', [BookingController::class, 'quote'])
        ->middleware('throttle:20,1');
    Route::post('/inquiries', [BookingInquiryController::class, 'store'])
        ->middleware('throttle:8,1');

    Route::prefix('admin')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:8,1');

        Route::middleware('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/pages', [AdminPageController::class, 'index']);
            Route::get('/pages/{page:id}', [AdminPageController::class, 'show']);
            Route::put('/pages/{page:id}', [AdminPageController::class, 'update']);
            Route::put('/pages/{page:id}/translations/{locale}', [AdminPageController::class, 'updateTranslation']);
            Route::put('/pages/{page:id}/blocks/{locale}', [AdminPageController::class, 'updateBlocks']);
            Route::post('/pages/{page:id}/ai', [AdminPageController::class, 'generate']);
            Route::get('/settings', [SettingsController::class, 'show']);
            Route::put('/settings', [SettingsController::class, 'update']);
            Route::post('/settings/test-openai', [SettingsController::class, 'testOpenAi']);
            Route::post('/settings/test-mail', [SettingsController::class, 'testMail'])
                ->middleware('throttle:10,1');
            Route::post('/settings/send-test-mail', [SettingsController::class, 'sendTestMail']);
            Route::get('/booking-settings', [BookingSettingsController::class, 'index']);
            Route::put('/booking-settings', [BookingSettingsController::class, 'update']);
            Route::get('/media', [MediaController::class, 'index']);
            Route::post('/media', [MediaController::class, 'store']);
            Route::get('/inquiries', [InquiryController::class, 'index']);
            Route::get('/stats', [InquiryController::class, 'stats']);
            Route::patch('/inquiries/{inquiry}', [InquiryController::class, 'update']);
            Route::post('/inquiries/{inquiry}/notify', [InquiryController::class, 'notify']);
            Route::delete('/inquiries/{inquiry}', [InquiryController::class, 'destroy']);
        });
    });
});

Route::get('/admin/{path?}', [SpaController::class, 'admin'])->where('path', '.*');
Route::get('/{path?}', SpaController::class)->where('path', '.*')->name('spa');

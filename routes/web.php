<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\UseDraftContent;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', LocaleRedirectController::class)->name('root');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Signed, short-lived preview links for signed-in staff. Never cached, never indexed.
Route::get('/preview/{locale}/{path?}', PreviewController::class)
    ->where('locale', Locales::PATTERN)
    ->where('path', '.*')
    ->middleware(['signed', 'auth', 'can:edit-content', UseDraftContent::class, SetLocale::class])
    ->name('preview');

Route::prefix('{locale}')
    ->where(['locale' => Locales::PATTERN])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', [PageController::class, 'home'])->name('home');
        Route::get('/projects/{slug}', [ProjectController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('projects.show');
        Route::get('/cv/download', CvController::class)->middleware('throttle:cv')->name('cv.download');
        Route::post('/contact-message', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
        Route::get('/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('page');
    });

// Dashboard language (stored on the user, so it follows them across devices).
Route::post('/admin/locale', function (Request $request) {
    $locale = $request->string('locale')->toString();
    abort_unless(Locales::isSupported($locale), 422);
    $request->user()?->forceFill(['locale' => $locale])->save();
    $request->session()->put('admin_locale', $locale);

    return back();
})->middleware(['web', 'auth'])->name('admin.locale');

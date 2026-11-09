<?php

namespace App\Providers;

use App\Content\Contracts\ContentReader;
use App\Content\MediaResolver;
use App\Content\Publisher;
use App\Content\Readers\PublishedReader;
use App\Content\Sections\SectionRegistry;
use App\Content\Site;
use App\Content\SubjectRegistry;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SectionRegistry::class);
        $this->app->singleton(SubjectRegistry::class);
        $this->app->singleton(Publisher::class);

        // Per request: visitors read published revisions; the preview middleware swaps in the draft reader.
        $this->app->scoped(ContentReader::class, PublishedReader::class);
        $this->app->scoped(MediaResolver::class);
        $this->app->scoped(Site::class);
    }

    public function boot(): void
    {
        // Owner-only abilities (editors work on drafts; only the owner puts them live or manages people).
        Gate::define('publish', fn (User $user) => $user->is_active && $user->isOwner());
        Gate::define('manage-settings', fn (User $user) => $user->is_active && $user->isOwner());
        Gate::define('manage-users', fn (User $user) => $user->is_active && $user->isOwner());
        Gate::define('view-activity', fn (User $user) => $user->is_active && $user->isOwner());
        Gate::define('edit-content', fn (User $user) => $user->is_active);

        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by('contact-min:'.$request->ip()),
            Limit::perHour(12)->by('contact-hour:'.$request->ip()),
        ]);
        RateLimiter::for('cv', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}

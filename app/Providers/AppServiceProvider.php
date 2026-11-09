<?php

namespace App\Providers;

use App\Content\Contracts\ContentReader;
use App\Content\MediaResolver;
use App\Content\Publisher;
use App\Content\Readers\PublishedReader;
use App\Content\Sections\SectionRegistry;
use App\Content\Site;
use App\Content\SubjectRegistry;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Experience;
use App\Models\Media;
use App\Models\Page;
use App\Models\ProcessStep;
use App\Models\Project;
use App\Models\Service;
use App\Models\SkillGroup;
use App\Models\SpokenLanguage;
use App\Models\Stat;
use App\Models\Technology;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\ContentPolicy;
use App\Policies\MediaPolicy;
use App\Policies\UserPolicy;
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
        // Model policies (deny by default for anything not listed).
        foreach ([Page::class, Project::class, Service::class, Experience::class, SkillGroup::class, SpokenLanguage::class,
            ProcessStep::class, Testimonial::class, Stat::class, Technology::class, Category::class] as $model) {
            Gate::policy($model, ContentPolicy::class);
        }
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);

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

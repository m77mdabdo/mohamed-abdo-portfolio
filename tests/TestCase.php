<?php

namespace Tests;

use App\Content\Import\ReferenceImporter;
use App\Content\Publisher;
use App\Content\Subjects\PageSubject;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /** Each test request starts with fresh request-scoped services, like a real request on the server. */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->forgetScopedInstances();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /** Imports the original content into the test database (files go to a fake disk). */
    protected function importReference(): void
    {
        Storage::fake('public');
        Queue::fake();
        app(ReferenceImporter::class)->run();
    }

    protected function owner(): User
    {
        return User::factory()->owner()->create();
    }

    protected function editor(): User
    {
        return User::factory()->editor()->create();
    }

    protected function home(): Page
    {
        return Page::query()->where('key', 'home')->firstOrFail();
    }

    protected function publishPage(Page $page, ?User $by = null): void
    {
        app(Publisher::class)->publish(new PageSubject($page->refresh()), $by);
    }
}

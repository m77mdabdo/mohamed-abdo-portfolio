<?php

namespace Tests\Feature;

use App\Content\Subjects\SettingsSubject;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The activity log names each subject in the reader's language, whatever language the change was made in. */
class ActivityLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_entries_are_shown_in_the_readers_language(): void
    {
        $this->importReference();
        app()->setLocale('en');
        $subject = new SettingsSubject('identity');
        $entry = ActivityLog::record('published', $subject->label().' (v2)', $subject->key());
        $this->assertStringStartsWith('Settings: ', $entry->description);

        app()->setLocale('ar');
        $shown = $entry->displayDescription();

        $this->assertStringStartsWith(__('Settings'), $shown);
        $this->assertStringNotContainsString('Settings:', $shown);
        $this->assertMatchesRegularExpression('/\(v\d+\)$/', $shown);
    }
}

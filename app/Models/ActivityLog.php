<?php

namespace App\Models;

use App\Content\SubjectRegistry;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $properties
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    /** @var array<string, array<string, string>|null> subject key => label per locale (one lookup per subject per request) */
    private static array $labels = [];

    /**
     * The description in the reader's language. Entries store the subject's name in the language of the person who
     * made the change; when the subject still exists, that name is swapped for its name in the current language.
     */
    public function displayDescription(): string
    {
        $labels = $this->subject ? self::labelsFor($this->subject) : null;
        if (! $labels) {
            return (string) $this->description;
        }
        $current = $labels[app()->getLocale()] ?? null;
        foreach ($labels as $label) {
            if ($current !== null && $label !== '' && str_starts_with((string) $this->description, $label)) {
                return $current.substr((string) $this->description, strlen($label));
            }
        }

        return (string) $this->description;
    }

    /** @return array<string, string>|null */
    private static function labelsFor(string $key): ?array
    {
        if (! array_key_exists($key, self::$labels)) {
            $subject = app(SubjectRegistry::class)->resolve($key);
            $was = app()->getLocale();
            $labels = null;
            if ($subject) {
                foreach (Locales::ALL as $locale) {
                    app()->setLocale($locale);
                    $labels[$locale] = $subject->label();
                }
                app()->setLocale($was);
            }
            self::$labels[$key] = $labels;
        }

        return self::$labels[$key];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $description, ?string $subject = null, array $properties = []): self
    {
        return static::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject' => $subject,
            'description' => mb_substr($description, 0, 255),
            'properties' => $properties ?: null,
        ]);
    }
}

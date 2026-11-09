<?php

namespace App\Models;

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

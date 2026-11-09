<?php

namespace App\Models;

use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * @property MessageStatus $status
 *
 * A message sent from the contact form. Private: never exported to the repository or public pages. */
class ContactMessage extends Model
{
    protected $guarded = ['id', 'status', 'notification_status', 'notification_error', 'notified_at'];

    protected function casts(): array
    {
        return ['status' => MessageStatus::class, 'notified_at' => 'datetime'];
    }
}

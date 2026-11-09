<?php

namespace App\Filament\Resources\Messages\Pages;

use App\Filament\Resources\Messages\ContactMessageResource;
use Filament\Resources\Pages\ManageRecords;

class ManageContactMessages extends ManageRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getSubheading(): ?string
    {
        $mailer = config('mail.default');

        return in_array($mailer, ['log', 'array'], true)
            ? __('E-mail notices are not delivered on this computer (mailer ":m"). Messages are still saved here.', ['m' => $mailer])
            : null;
    }
}

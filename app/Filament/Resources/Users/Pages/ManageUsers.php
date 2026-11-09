<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('Add user'))
                ->using(function (array $data) {
                    $user = new User(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => $data['password']]);
                    $user->forceFill(['role' => $data['role'], 'is_active' => $data['is_active'] ?? true])->save();
                    ActivityLog::record('user_created', $user->email.' ('.$user->role->value.')', 'user:'.$user->id);

                    return $user;
                }),
        ];
    }
}

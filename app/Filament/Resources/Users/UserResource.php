<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

/** People who can sign in to the dashboard (owner only). There is no public registration. */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Inbox & people');
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users & permissions');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(120),
            TextInput::make('email')->label(__('E-mail'))->email()->required()->maxLength(190)->unique(ignoreRecord: true),
            Select::make('role')->label(__('Role'))->options(Role::options())->required()->native(false)
                ->helperText(__('Editor: edits drafts and reads messages. Owner: also publishes, restores, deletes and manages people and settings.'))
                ->disabled(fn (?User $record) => $record?->is(auth()->user())),
            Toggle::make('is_active')->label(__('Can sign in'))->default(true)->disabled(fn (?User $record) => $record?->is(auth()->user())),
            TextInput::make('password')->label(__('Password'))->password()->revealable()
                ->required(fn (?User $record) => $record === null)
                ->rule(Password::min(12)->letters()->numbers())
                ->dehydrated(fn ($state) => filled($state))
                ->helperText(fn (?User $record) => $record ? __('Leave empty to keep the current password.') : __('At least 12 characters with letters and numbers. Share it privately.')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->description(fn (User $u) => $u->email)->searchable(['name', 'email']),
                TextColumn::make('role')->label(__('Role'))->badge()->formatStateUsing(fn (Role $state) => $state->label()),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
                IconColumn::make('mfa')->label(__('Two-factor'))->boolean()->state(fn (User $u) => filled($u->app_authentication_secret)),
                TextColumn::make('last_login_at')->label(__('Last sign-in'))->since()->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->using(function (User $record, array $data) {
                    $record->fill(array_intersect_key($data, array_flip(['name', 'email', 'password'])));
                    if (! $record->is(auth()->user())) {
                        $demotesLastOwner = $record->role === Role::Owner && ($data['role'] ?? 'owner') !== Role::Owner->value
                            && User::query()->where('role', Role::Owner->value)->where('is_active', true)->count() <= 1;
                        if (! $demotesLastOwner) {
                            $record->forceFill(['role' => $data['role'] ?? $record->role, 'is_active' => (bool) ($data['is_active'] ?? true)]);
                        }
                    }
                    $record->save();
                    ActivityLog::record('user_updated', $record->email, 'user:'.$record->id);

                    return $record;
                }),
                Action::make('resetMfa')->label(__('Reset two-factor'))->icon('heroicon-o-key')->color('warning')
                    ->visible(fn (User $u) => filled($u->app_authentication_secret) && ! $u->is(auth()->user()))
                    ->requiresConfirmation()
                    ->action(function (User $u) {
                        $u->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
                        Notification::make()->success()->title(__('Two-factor sign-in was reset for this user.'))->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}

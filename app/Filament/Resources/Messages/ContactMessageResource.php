<?php

namespace App\Filament\Resources\Messages;

use App\Enums\MessageStatus;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/** Messages from the contact form. Stored first, always — the e-mail notice status is shown honestly. */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'contact-messages';

    public static function getNavigationGroup(): ?string
    {
        return __('Inbox & people');
    }

    public static function getModelLabel(): string
    {
        return __('Message');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Messages');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = ContactMessage::query()->where('status', MessageStatus::New->value)->count();

        return $n ? (string) $n : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(['default' => 1, 'md' => 2])->schema([
                TextEntry::make('name')->label(__('Name')),
                TextEntry::make('email')->label(__('E-mail'))->url(fn (ContactMessage $m) => 'mailto:'.$m->email)->copyable(),
                TextEntry::make('project_type')->label(__('Project type'))->placeholder('—'),
                TextEntry::make('created_at')->label(__('Received'))->dateTime('Y-m-d H:i', 'Asia/Riyadh'),
                TextEntry::make('locale')->label(__('Site language'))->formatStateUsing(fn ($state) => $state === 'ar' ? 'العربية' : 'English'),
                TextEntry::make('notification_status')->label(__('E-mail notice'))->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'sent' => __('Sent'), 'skipped' => __('Not sent'), 'failed' => __('Failed'), default => __('Waiting')
                    })
                    ->color(fn ($state) => match ($state) {
                        'sent' => 'success', 'failed' => 'danger', default => 'gray'
                    })
                    ->helperText(fn (ContactMessage $m) => $m->notification_error),
            ]),
            TextEntry::make('message')->label(__('Message'))->placeholder('—')->extraAttributes(['style' => 'white-space:pre-wrap']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('status')->label(__('Status'))->badge()
                    ->formatStateUsing(fn (MessageStatus $state) => $state->label())->color(fn (MessageStatus $state) => $state->color()),
                TextColumn::make('name')->label(__('Name'))->searchable()->description(fn (ContactMessage $m) => $m->email),
                TextColumn::make('project_type')->label(__('Project type'))->placeholder('—')->toggleable(),
                TextColumn::make('message')->label(__('Message'))->limit(60)->searchable()->toggleable(),
                TextColumn::make('notification_status')->label(__('E-mail notice'))->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'sent' => __('Sent'), 'skipped' => __('Not sent'), 'failed' => __('Failed'), default => __('Waiting')
                    })
                    ->color(fn ($state) => match ($state) {
                        'sent' => 'success', 'failed' => 'danger', default => 'gray'
                    })->toggleable(),
                TextColumn::make('created_at')->label(__('Received'))->since()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->label(__('Status'))->options(MessageStatus::options())])
            ->recordActions([
                ViewAction::make()->slideOver()->after(fn (ContactMessage $m) => $m->status === MessageStatus::New ? $m->forceFill(['status' => MessageStatus::Read])->save() : null),
                Action::make('setStatus')->label(__('Status'))->icon('heroicon-o-flag')->color('gray')
                    ->schema([Select::make('status')->label(__('Status'))->options(MessageStatus::options())->required()->native(false)])
                    ->fillForm(fn (ContactMessage $m) => ['status' => $m->status->value])
                    ->action(fn (ContactMessage $m, array $data) => $m->forceFill(['status' => $data['status']])->save()),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('archive')->label(__('Archive'))->icon('heroicon-o-archive-box')
                    ->action(fn (Collection $records) => $records->each(fn ($m) => $m->forceFill(['status' => MessageStatus::Archived])->save())),
                DeleteBulkAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContactMessages::route('/')];
    }
}

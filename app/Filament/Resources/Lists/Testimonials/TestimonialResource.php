<?php

namespace App\Filament\Resources\Lists\Testimonials;

use App\Filament\Support\Bilingual;
use App\Filament\Support\CollectionTable;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** Shared list "Testimonials": used by sections on several pages; published as one list. */
class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 16;

    public static function getNavigationGroup(): ?string
    {
        return __('Lists');
    }

    public static function getModelLabel(): string
    {
        return __('Testimonial');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Testimonials');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label(__('Client name'))->required()->maxLength(120),
            Bilingual::field('role', 'Client role / company', max: 120),
            Bilingual::field('quote', 'Quote (exact words of the client)', true, 600, true),
            Toggle::make('has_consent')->label(__('The client agreed to be quoted by name'))->helperText(__('Quotes without consent are never shown on the site.')),
            Toggle::make('is_visible')->label(__('Visible'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('name')->label(__('Client'))->description(fn ($record) => Str::limit($record->tr('quote'), 80)), IconColumn::make('has_consent')->label(__('Consent'))->boolean()], true);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTestimonials::route('/')];
    }
}

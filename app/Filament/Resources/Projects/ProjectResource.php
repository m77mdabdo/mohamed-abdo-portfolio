<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

/**
 * Projects: every field of a project page (story, facts, stack, cover, desktop and mobile galleries, links, SEO).
 * Saving keeps a draft; "Publish" on the project puts it live.
 */
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getNavigationGroup(): ?string
    {
        return __('Projects');
    }

    public static function getModelLabel(): string
    {
        return __('Project');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Projects');
    }

    /** Arabic + English inputs side by side for one translatable column. */
    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function state(Project $project): string
    {
        return ProjectsTable::state($project);
    }

    public static function canDelete($record): bool
    {
        return Gate::allows('publish');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}

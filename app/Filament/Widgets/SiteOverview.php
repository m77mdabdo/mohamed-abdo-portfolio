<?php

namespace App\Filament\Widgets;

use App\Enums\MessageStatus;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            Stat::make(__('New messages'), ContactMessage::query()->where('status', MessageStatus::New->value)->count())
                ->url(route('filament.admin.resources.contact-messages.index'))->icon('heroicon-o-inbox-arrow-down'),
            Stat::make(__('Projects'), Project::query()->count())->url(route('filament.admin.resources.projects.index'))->icon('heroicon-o-squares-2x2'),
            Stat::make(__('Pages'), Page::query()->count())->url(route('filament.admin.resources.pages.index'))->icon('heroicon-o-document-duplicate'),
            Stat::make(__('Files'), Media::query()->count())->description(Media::query()->where('status', 'failed')->exists() ? __('Some optimisations failed') : null)
                ->url(route('filament.admin.resources.media.index'))->icon('heroicon-o-photo'),
        ];
    }
}

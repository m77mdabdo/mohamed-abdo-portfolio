<?php

namespace App\Filament\Resources\Activity\Pages;

use App\Filament\Resources\Activity\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivity extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}

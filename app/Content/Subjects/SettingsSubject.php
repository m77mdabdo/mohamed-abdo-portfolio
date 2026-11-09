<?php

namespace App\Content\Subjects;

use App\Content\Contracts\ContentSubject;
use App\Content\Fields\FieldSchema;
use App\Content\Settings\SettingsGroups;
use App\Content\Settings\UiStrings;
use App\Models\SiteSetting;

final class SettingsSubject implements ContentSubject
{
    public function __construct(private readonly string $group) {}

    public function key(): string
    {
        return 'settings:'.$this->group;
    }

    public function label(): string
    {
        return __('Settings').': '.(SettingsGroups::labels()[$this->group] ?? $this->group);
    }

    public function group(): string
    {
        return $this->group;
    }

    public function model(): SiteSetting
    {
        return SiteSetting::query()->firstOrCreate(['group' => $this->group], ['data' => []]);
    }

    public function draft(): array
    {
        $data = $this->model()->data ?? [];
        if ($this->group === 'ui') {
            return UiStrings::cleanOverrides($data);
        }

        return FieldSchema::clean(SettingsGroups::fields($this->group), $data);
    }

    public function restore(array $snapshot): void
    {
        $model = $this->model();
        $model->data = $snapshot;
        $model->save();
    }
}

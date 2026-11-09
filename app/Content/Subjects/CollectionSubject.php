<?php

namespace App\Content\Subjects;

use App\Content\Collections;
use App\Content\Contracts\ContentSubject;
use App\Support\Tr;

final class CollectionSubject implements ContentSubject
{
    public function __construct(private readonly string $type) {}

    public function key(): string
    {
        return 'collection:'.$this->type;
    }

    public function label(): string
    {
        return __('List').': '.Collections::label($this->type);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function draft(): array
    {
        $class = Collections::model($this->type);
        $columns = Collections::columns($this->type);

        return $class::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($row) => ['id' => $row->id] + array_map(
                // translatable values in canonical form so key order or blank strings never count as a change
                fn ($v) => is_array($v) && (array_key_exists('en', $v) || array_key_exists('ar', $v)) ? Tr::normalize($v) : $v,
                $row->only($columns),
            ))
            ->values()->all();
    }

    public function restore(array $snapshot): void
    {
        $class = Collections::model($this->type);
        $columns = Collections::columns($this->type);
        $ids = [];
        foreach ($snapshot as $row) {
            $model = $class::query()->findOrNew($row['id'] ?? null);
            if (! $model->exists && isset($row['id'])) {
                $model->id = $row['id'];
            }
            foreach ($columns as $column) {
                if (array_key_exists($column, $row)) {
                    $model->setAttribute($column, $row[$column]);
                }
            }
            $model->save();
            $ids[] = $model->id;
        }
        if (Collections::prunes($this->type)) {
            $class::query()->whereNotIn('id', $ids)->delete();
        }
    }
}

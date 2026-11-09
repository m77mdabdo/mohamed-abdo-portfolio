<?php

namespace App\Models\Concerns;

use App\Support\Tr;

/** Convenience reader for JSON {"en","ar"} columns. */
trait HasTranslatableFields
{
    public function tr(string $attribute, ?string $locale = null): string
    {
        return Tr::pick($this->getAttribute($attribute), $locale);
    }
}

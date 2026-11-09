<?php

namespace App\Rules;

use App\Support\SafeUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeLinkUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && $value !== null) {
            $fail(__('The link is not valid.'));

            return;
        }
        if (! SafeUrl::isAllowed($value)) {
            $fail(__('Use a full https:// address, mailto:, tel:, a path starting with / or an #anchor.'));
        }
    }
}

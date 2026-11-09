<?php

namespace App\Content\Readers;

use App\Content\Contracts\ContentReader;
use App\Content\SubjectRegistry;

/** Working copy for signed-in staff on a signed preview link. Never cached, never shown to visitors. */
final class DraftReader implements ContentReader
{
    /** @var array<string, array|null> */
    private array $memo = [];

    public function __construct(private readonly SubjectRegistry $registry) {}

    public function get(string $subject): ?array
    {
        return $this->memo[$subject] ??= $this->registry->resolve($subject)?->draft();
    }

    public function all(string $prefix): array
    {
        $out = [];
        foreach ($this->registry->forPrefix($prefix) as $subject) {
            $out[$subject->key()] = $this->memo[$subject->key()] ??= $subject->draft();
        }

        return $out;
    }

    public function isPreview(): bool
    {
        return true;
    }
}

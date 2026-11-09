<?php

namespace App\Content\Contracts;

/**
 * Something that can be published: a page, a project, a settings group, a menu or a shared list.
 * The working copy (draft) lives in normal tables; publishing freezes draft() into an immutable revision.
 */
interface ContentSubject
{
    /** Stable key such as "page:3" or "settings:identity". */
    public function key(): string;

    /** Name shown in the dashboard. */
    public function label(): string;

    /** Complete, deterministic snapshot of the working copy. */
    public function draft(): array;

    /** Writes a snapshot back into the working copy (used by "restore" and "discard changes"). */
    public function restore(array $snapshot): void;
}

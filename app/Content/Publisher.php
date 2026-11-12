<?php

namespace App\Content;

use App\Content\Contracts\ContentSubject;
use App\Models\ActivityLog;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Publishing workflow. publish() freezes the working copy into a new immutable revision (the live version);
 * restore() copies an old revision back into the working copy as a draft. Both run in a transaction and the
 * affected cache entries are cleared only after the transaction commits.
 */
final class Publisher
{
    public function publish(ContentSubject $subject, ?User $by = null, ?string $note = null): ContentRevision
    {
        $revision = DB::transaction(function () use ($subject, $by, $note) {
            $snapshot = $subject->draft();
            $hash = self::hash($snapshot);
            $latest = ContentRevision::query()->where('subject', $subject->key())->orderByDesc('version')->lockForUpdate()->first();
            if ($latest && $latest->hash === $hash) {
                return $latest; // nothing changed: no duplicate history entry
            }

            $revision = ContentRevision::query()->create([
                'subject' => $subject->key(),
                'version' => ($latest !== null ? $latest->version : 0) + 1,
                'snapshot' => $snapshot,
                'hash' => $hash,
                'note' => $note ? mb_substr($note, 0, 255) : null,
                'published_by' => $by?->id,
            ]);
            ActivityLog::record('published', $subject->label().' (v'.$revision->version.')', $subject->key());
            // Visitors keep the old cached version until this transaction has committed.
            DB::afterCommit(fn () => ContentCache::forget($subject->key()));

            return $revision;
        });

        return $revision;
    }

    /** Copies a revision into the working copy. With $publish the restored version also goes live. */
    public function restore(ContentSubject $subject, ContentRevision $revision, ?User $by = null, bool $publish = false): void
    {
        abort_unless($revision->subject === $subject->key(), 422);

        DB::transaction(function () use ($subject, $revision) {
            $subject->restore($revision->snapshot);
            ActivityLog::record('restored', $subject->label().' ← v'.$revision->version, $subject->key());
        });

        if ($publish) {
            $this->publish($subject, $by, __('Restored from version :v', ['v' => $revision->version]));
        }
    }

    /** Throws away unpublished changes by restoring the live version. */
    public function discard(ContentSubject $subject): bool
    {
        $live = $this->live($subject->key());
        if (! $live) {
            return false;
        }
        DB::transaction(fn () => $subject->restore($live->snapshot));
        ActivityLog::record('discarded', $subject->label(), $subject->key());

        return true;
    }

    public function live(string $subjectKey): ?ContentRevision
    {
        return ContentRevision::query()->where('subject', $subjectKey)->orderByDesc('version')->first();
    }

    /** True when the working copy differs from what visitors see (or was never published). */
    public function hasUnpublishedChanges(ContentSubject $subject): bool
    {
        $live = ContentRevision::query()->where('subject', $subject->key())->orderByDesc('version')->value('hash');

        return $live !== self::hash($subject->draft());
    }

    /**
     * Fingerprint of a snapshot. Object keys are sorted first, so the same content always gives the same hash
     * whatever order the database returns JSON keys in (MySQL reorders them, MariaDB and SQLite do not).
     */
    public static function hash(array $snapshot): string
    {
        return sha1(json_encode(self::canonical($snapshot), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private static function canonical(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $value[$k] = self::canonical($v);
            }
        }

        return $value;
    }

    /** Recomputes stored fingerprints (after a hashing change or a move between database engines). */
    public static function rehashAll(): int
    {
        $n = 0;
        ContentRevision::query()->orderBy('id')->each(function (ContentRevision $r) use (&$n) {
            $hash = self::hash($r->snapshot);
            if ($hash !== $r->hash) {
                ContentRevision::query()->whereKey($r->id)->update(['hash' => $hash]);
                $n++;
            }
        });

        return $n;
    }
}

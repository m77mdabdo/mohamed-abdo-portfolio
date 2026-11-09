<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Turns a plain-text title into safe markup without letting editors write HTML:
 *   *words*  → highlighted (the turquoise marker of the design)
 *   newline  → line break
 * Everything is escaped first, so any "<script>" typed in the dashboard is shown as text.
 */
final class RichTitle
{
    public static function render(string $text): HtmlString
    {
        $safe = e(str_replace("\r\n", "\n", trim($text)));
        $safe = preg_replace('/\*([^*\n]+)\*/u', '<mark class="hl">$1</mark>', $safe) ?? $safe;

        return new HtmlString(nl2br($safe, false));
    }

    /** The same text without markers, for <title>, alt text and meta tags. */
    public static function plain(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace('*', '', $text)) ?? $text);
    }

    /**
     * Splits text into word spans for the reveal animations. Splitting happens only on whitespace,
     * so Arabic letters inside a word stay joined.
     */
    public static function words(string $text, bool $mask = false): HtmlString
    {
        $parts = preg_split('/(\s+)/u', trim($text), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $html = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('/^\s+$/u', $part)) {
                $html .= ' ';

                continue;
            }
            $word = '<span class="w">'.e($part).'</span>';
            $html .= $mask ? '<span class="m">'.$word.'</span>' : $word;
        }

        return new HtmlString($html);
    }
}

<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Markdown
{
    /**
     * Roughly how many characters of prose fit before a block is worth collapsing.
     */
    public const LONG_CHARS = 420;

    /**
     * Roughly how many lines of prose fit before a block is worth collapsing.
     */
    public const LONG_LINES = 7;

    /**
     * Renders block-level Markdown to HTML.
     *
     * Raw HTML is escaped and unsafe links are dropped.
     *
     * @param  string|null  $text
     * @return HtmlString
     */
    public static function render(?string $text): HtmlString
    {
        if ($text === null || trim($text) === '') {
            return new HtmlString('');
        }

        return new HtmlString(Str::markdown($text, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]));
    }

    /**
     * Whether a block deserves to be collapsed behind a "show more".
     *
     * @param  string|null  $text
     * @return bool
     */
    public static function isLong(?string $text): bool
    {
        if ($text === null) {
            return false;
        }

        return mb_strlen($text) > self::LONG_CHARS
            || substr_count(trim($text), "\n") + 1 > self::LONG_LINES;
    }
}

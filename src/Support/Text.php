<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Text
{
    /**
     * Renders one line of inline Markdown, escaping raw HTML and dropping unsafe links.
     *
     * @param  string|null  $text
     * @return HtmlString
     */
    public static function inline(?string $text): HtmlString
    {
        if ($text === null || $text === '') {
            return new HtmlString('');
        }

        return new HtmlString(rtrim(Str::inlineMarkdown($text, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ])));
    }
}

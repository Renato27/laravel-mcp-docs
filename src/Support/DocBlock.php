<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use ReflectionClass;

class DocBlock
{
    /**
     * Holds the parsed summary and long-form description of a docblock.
     *
     * @param  string|null  $summary
     * @param  string|null  $description
     * @return void
     */
    protected function __construct(
        public readonly ?string $summary,
        public readonly ?string $description,
    ) {}

    /**
     * Reads the long-form, human-facing docblock of a class.
     *
     * @param  class-string  $class
     * @return self
     */
    public static function forClass(string $class): self
    {
        $raw = (new ReflectionClass($class))->getDocComment();

        return $raw === false ? self::empty() : self::parse($raw);
    }

    /**
     * Builds prose given directly as a string or list of paragraphs; the first paragraph is the summary.
     *
     * @param  string|list<string>  $text
     * @return self
     */
    public static function fromText(string|array $text): self
    {
        $text = is_array($text) ? implode("\n\n", array_map('strval', $text)) : $text;

        [$summary, $description] = self::splitProse(preg_split('/\R/', $text) ?: []);

        return new self($summary, $description);
    }

    /**
     * A docblock with no prose.
     *
     * @return self
     */
    public static function empty(): self
    {
        return new self(null, null);
    }

    /**
     * Parses the prose of a raw docblock: every line before the first tag, so annotations never leak into it.
     *
     * @param  string  $raw
     * @return self
     */
    public static function parse(string $raw): self
    {
        $prose = [];

        foreach (self::stripCommentSyntax($raw) as $line) {
            if (str_starts_with($line, '@')) {
                break;
            }

            $prose[] = $line;
        }

        [$summary, $description] = self::splitProse($prose);

        return new self($summary, $description);
    }

    /**
     * Whether the docblock carries no prose.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->summary === null && $this->description === null;
    }

    /**
     * The prose as an array.
     *
     * @return array{summary: string|null, description: string|null}
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'description' => $this->description,
        ];
    }

    /**
     * Strips the comment delimiters and leading asterisks, trimming blank lines from both ends only.
     *
     * @param  string  $raw
     * @return list<string>
     */
    protected static function stripCommentSyntax(string $raw): array
    {
        $raw = preg_replace('#^\s*/\*\*+#', '', $raw) ?? $raw;
        $raw = preg_replace('#\*+/\s*$#', '', $raw) ?? $raw;

        $lines = [];

        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $lines[] = rtrim(preg_replace('/^\s*\*\s?/', '', $line) ?? $line);
        }

        while ($lines !== [] && trim($lines[0]) === '') {
            array_shift($lines);
        }

        while ($lines !== [] && trim((string) end($lines)) === '') {
            array_pop($lines);
        }

        return array_values($lines);
    }

    /**
     * Splits prose into its first paragraph (the summary) and the rest (the description).
     *
     * @param  list<string>  $prose
     * @return array{0: string|null, 1: string|null}
     */
    protected static function splitProse(array $prose): array
    {
        $text = trim(implode("\n", $prose));

        if ($text === '') {
            return [null, null];
        }

        $parts = preg_split('/\R\s*\R/', $text, 2) ?: [$text];

        $summary = trim(str_replace("\n", ' ', $parts[0]));
        $description = isset($parts[1]) ? trim($parts[1]) : null;

        return [$summary === '' ? null : $summary, $description === '' ? null : $description];
    }
}

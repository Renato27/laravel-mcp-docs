<?php

declare(strict_types=1);

use LaravelMcpDocs\Support\Markdown;

it('renders nothing for empty input', function (): void {
    expect((string) Markdown::render(null))->toBe('')
        ->and((string) Markdown::render('   '))->toBe('');
});

it('renders headings, lists and paragraphs', function (): void {
    $html = (string) Markdown::render(<<<'MD'
        EricaPRO covers companies in Spain and Portugal.

        ## Tools
        - **search-companies-tool**: the main tool.
        - **get-company-tool**: fetch one company.

        Call the search tool first.
        MD);

    expect($html)
        ->toContain('Tools')
        ->toContain('<ul>')
        ->toContain('<li>')
        ->toContain('search-companies-tool')
        ->toContain('Call the search tool first.');
});

it('renders fenced code verbatim', function (): void {
    $html = (string) Markdown::render("Example:\n\n```\n{\"a\": 1}\n```");

    expect($html)->toContain('<pre>')
        ->and($html)->toContain('&quot;a&quot;');
});

it('escapes HTML rather than passing it through', function (): void {
    $html = (string) Markdown::render('Watch out <script>alert(1)</script> here.');

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;');
});

it('treats short text as short and long text as long', function (): void {
    expect(Markdown::isLong('A short line.'))->toBeFalse()
        ->and(Markdown::isLong(null))->toBeFalse()
        ->and(Markdown::isLong(str_repeat('word ', 200)))->toBeTrue()
        ->and(Markdown::isLong(implode("\n", array_fill(0, 20, 'line'))))->toBeTrue();
});

it('renders single-asterisk emphasis', function (): void {
    expect((string) LaravelMcpDocs\Support\Text::inline('Added to *local* scope.'))
        ->toBe('Added to <em>local</em> scope.');
});

it('does not mistake bold for two emphases', function (): void {
    expect((string) LaravelMcpDocs\Support\Text::inline('This is **bold** here.'))
        ->toBe('This is <strong>bold</strong> here.');
});

it('leaves a lone asterisk alone', function (): void {
    expect((string) LaravelMcpDocs\Support\Text::inline('2 * 3 = 6'))->toBe('2 * 3 = 6');
});

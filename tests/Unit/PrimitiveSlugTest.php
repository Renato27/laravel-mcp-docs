<?php

declare(strict_types=1);

use LaravelMcpDocs\Collector\PrimitiveDocument;
use LaravelMcpDocs\Support\DocBlock;

function primitive(string $name): PrimitiveDocument
{
    return new PrimitiveDocument($name, [], null, DocBlock::parse(''));
}

it('leaves an already-safe name alone', function (): void {
    expect(primitive('search-companies-tool')->slug())->toBe('search-companies-tool');
});

it('slugs a resource named in prose', function (): void {
    // Resources carry a human title, so the raw name would produce an id with
    // spaces -- which getElementById never matches, leaving the toggle dead.
    expect(primitive('Business Vocabulary and Thresholds')->slug())
        ->toBe('business-vocabulary-and-thresholds');
});

it('collapses punctuation and trims the edges', function (): void {
    expect(primitive('  Orders / Refunds (v2)  ')->slug())->toBe('orders-refunds-v2');
});

it('keeps underscores out of the way of a valid id', function (): void {
    expect(primitive('search_companies_guided')->slug())->toBe('search-companies-guided');
});

it('falls back rather than emitting an empty id', function (): void {
    expect(primitive('///')->slug())->toBe('primitive');
});

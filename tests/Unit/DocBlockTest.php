<?php

declare(strict_types=1);

use LaravelMcpDocs\Support\DocBlock;

it('splits the summary from the body', function (): void {
    $block = DocBlock::parse(<<<'DOC'
        /**
         * Creates an order.
         *
         * The cart must already carry an address.
         * Retries are safe.
         */
        DOC);

    expect($block->summary)->toBe('Creates an order.')
        ->and($block->description)->toBe("The cart must already carry an address.\nRetries are safe.");
});

it('joins a multi-line summary into one line', function (): void {
    $block = DocBlock::parse(<<<'DOC'
        /**
         * Creates an order from a cart
         * that already has an address.
         */
        DOC);

    expect($block->summary)->toBe('Creates an order from a cart that already has an address.')
        ->and($block->description)->toBeNull();
});

it('keeps annotations out of the prose', function (): void {
    $block = DocBlock::parse(<<<'DOC'
        /**
         * Searches orders.
         *
         * @param string $customer The customer.
         * @return array<string, mixed>
         */
        DOC);

    expect($block->summary)->toBe('Searches orders.')
        ->and($block->description)->toBeNull();
});

it('treats a docblock with only annotations as empty', function (): void {
    $block = DocBlock::parse("/**\n * @internal\n */");

    expect($block->isEmpty())->toBeTrue();
});

it('reports an empty docblock for a class without one', function (): void {
    expect(DocBlock::forClass(stdClass::class)->isEmpty())->toBeTrue();
});

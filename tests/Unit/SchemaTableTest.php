<?php

declare(strict_types=1);

use LaravelMcpDocs\Support\SchemaTable;

it('returns nothing for a schema without properties', function (): void {
    expect(SchemaTable::rows(null))->toBe([])
        ->and(SchemaTable::rows(['type' => 'object']))->toBe([]);
});

it('marks required properties', function (): void {
    $rows = SchemaTable::rows([
        'type' => 'object',
        'required' => ['cart_id'],
        'properties' => [
            'cart_id' => ['type' => 'string'],
            'note' => ['type' => 'string'],
        ],
    ]);

    expect($rows[0]['name'])->toBe('cart_id')
        ->and($rows[0]['required'])->toBeTrue()
        ->and($rows[1]['required'])->toBeFalse();
});

it('describes enums, defaults and typed arrays', function (): void {
    $rows = SchemaTable::rows([
        'type' => 'object',
        'properties' => [
            'status' => ['type' => 'string', 'enum' => ['paid', 'pending']],
            'limit' => ['type' => 'integer', 'default' => 25],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
            'active' => ['type' => 'boolean', 'default' => false],
        ],
    ]);

    expect($rows[0]['enum'])->toBe(['paid', 'pending'])
        ->and($rows[1]['default'])->toBe('25')
        ->and($rows[2]['type'])->toBe('string[]')
        ->and($rows[3]['default'])->toBe('false');
});

it('flattens nested objects with dotted names', function (): void {
    $rows = SchemaTable::rows([
        'type' => 'object',
        'properties' => [
            'address' => [
                'type' => 'object',
                'required' => ['city'],
                'properties' => [
                    'city' => ['type' => 'string'],
                    'zip' => ['type' => 'string'],
                ],
            ],
        ],
    ]);

    expect(array_column($rows, 'name'))->toBe(['address', 'address.city', 'address.zip'])
        ->and($rows[1]['required'])->toBeTrue();
});

it('expands the element shape of an array of objects', function (): void {
    $rows = SchemaTable::rows([
        'type' => 'object',
        'properties' => [
            'lines' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => ['sku' => ['type' => 'string']],
                ],
            ],
        ],
    ]);

    expect(array_column($rows, 'name'))->toBe(['lines', 'lines[].sku']);
});

<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

class SchemaTable
{
    /**
     * Flattens a JSON Schema into rows, expanding nested objects with dotted names.
     *
     * @param  array<string, mixed>|null  $schema
     * @param  string  $prefix
     * @return list<array{name: string, type: string, required: bool, default: string|null, description: string|null, enum: list<string>}>
     */
    public static function rows(?array $schema, string $prefix = ''): array
    {
        if ($schema === null) {
            return [];
        }

        $properties = $schema['properties'] ?? null;

        if (! is_array($properties)) {
            return [];
        }

        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        $rows = [];

        foreach ($properties as $name => $property) {
            if (! is_string($name) || ! is_array($property)) {
                continue;
            }

            $path = $prefix === '' ? $name : "{$prefix}.{$name}";

            $rows[] = [
                'name' => $path,
                'type' => self::type($property),
                'required' => in_array($name, $required, true),
                'default' => self::literal($property['default'] ?? null),
                'description' => is_string($property['description'] ?? null) ? $property['description'] : null,
                'enum' => self::enum($property),
            ];

            foreach (self::children($property, $path) as $child) {
                $rows[] = $child;
            }
        }

        return $rows;
    }

    /**
     * A readable type for a property: a union, an element type with `[]`, or the plain type.
     *
     * @param  array<string, mixed>  $property
     * @return string
     */
    protected static function type(array $property): string
    {
        $type = $property['type'] ?? null;

        if (is_array($type)) {
            return implode(' | ', array_map('strval', $type));
        }

        if (! is_string($type)) {
            return isset($property['anyOf']) || isset($property['oneOf']) ? 'union' : 'any';
        }

        if ($type === 'array' && is_array($property['items'] ?? null)) {
            return self::type($property['items']).'[]';
        }

        return $type;
    }

    /**
     * The property's allowed values as strings.
     *
     * @param  array<string, mixed>  $property
     * @return list<string>
     */
    protected static function enum(array $property): array
    {
        $enum = $property['enum'] ?? null;

        if (! is_array($enum)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $value): string => is_scalar($value) ? (string) $value : (string) json_encode($value),
            $enum,
        ));
    }

    /**
     * Rows for a nested object, or for the element shape of an array of objects under `items`.
     *
     * @param  array<string, mixed>  $property
     * @param  string  $path
     * @return list<array<string, mixed>>
     */
    protected static function children(array $property, string $path): array
    {
        if (is_array($property['properties'] ?? null)) {
            return self::rows($property, $path);
        }

        if (is_array($property['items'] ?? null) && is_array($property['items']['properties'] ?? null)) {
            return self::rows($property['items'], $path.'[]');
        }

        return [];
    }

    /**
     * A default value as display text, or null when there is none.
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected static function literal(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }
}

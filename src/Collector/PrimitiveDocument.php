<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Collector;

use LaravelMcpDocs\Support\DocBlock;

class PrimitiveDocument
{
    /**
     * Holds one tool, resource, resource template or prompt as the server advertises it, plus its class prose.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $payload  Verbatim wire payload from the list method.
     * @param  string|null  $class
     * @param  DocBlock  $docBlock
     * @param  bool  $hidden
     */
    public function __construct(
        public readonly string $name,
        public readonly array $payload,
        public readonly ?string $class,
        public readonly DocBlock $docBlock,
        public readonly bool $hidden = false,
    ) {
    }

    /**
     * The name reduced to something an HTML id and a URL fragment can carry.
     *
     * @return string
     */
    public function slug(): string
    {
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $this->name));

        return trim($slug, '-') ?: 'primitive';
    }

    /**
     * The advertised title, or null when there is none.
     *
     * @return string|null
     */
    public function title(): ?string
    {
        $title = $this->payload['title'] ?? null;

        return is_string($title) && $title !== '' ? $title : null;
    }

    /**
     * The description written for the model.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        $description = $this->payload['description'] ?? null;

        return is_string($description) && $description !== '' ? $description : null;
    }

    /**
     * The advertised input schema, or null when there is none.
     *
     * @return array<string, mixed>|null
     */
    public function inputSchema(): ?array
    {
        return is_array($this->payload['inputSchema'] ?? null) ? $this->payload['inputSchema'] : null;
    }

    /**
     * The advertised output schema, or null when there is none.
     *
     * @return array<string, mixed>|null
     */
    public function outputSchema(): ?array
    {
        return is_array($this->payload['outputSchema'] ?? null) ? $this->payload['outputSchema'] : null;
    }

    /**
     * Behavioural hints such as readOnlyHint or destructiveHint.
     *
     * @return array<string, mixed>
     */
    public function annotations(): array
    {
        $annotations = $this->payload['annotations'] ?? [];

        return is_array($annotations) ? $annotations : [];
    }

    /**
     * Behavioural hints turned into labels a non-developer can read.
     *
     * @return list<array{label: string, tone: string}>
     */
    public function badges(): array
    {
        $map = [
            'readOnlyHint' => ['Read only', 'safe'],
            'idempotentHint' => ['Idempotent', 'neutral'],
            'destructiveHint' => ['Destructive', 'danger'],
            'openWorldHint' => ['Reaches outside the app', 'warn'],
        ];

        $badges = [];

        foreach ($this->annotations() as $key => $value) {
            if ($value !== true || ! isset($map[$key])) {
                continue;
            }

            [$label, $tone] = $map[$key];

            $badges[] = ['label' => $label, 'tone' => $tone];
        }

        if ($this->hidden) {
            $badges[] = ['label' => 'Via tool search', 'tone' => 'warn'];
        }

        return $badges;
    }

    /**
     * Arguments a prompt accepts; empty for other primitives.
     *
     * @return list<array<string, mixed>>
     */
    public function arguments(): array
    {
        $arguments = $this->payload['arguments'] ?? [];

        return is_array($arguments) ? array_values(array_filter($arguments, 'is_array')) : [];
    }

    /**
     * The URI a resource is read from; null for other primitives.
     *
     * @return string|null
     */
    public function uri(): ?string
    {
        $uri = $this->payload['uri'] ?? $this->payload['uriTemplate'] ?? null;

        return is_string($uri) && $uri !== '' ? $uri : null;
    }

    /**
     * The primitive as a plain array: its identity and docs merged over the wire payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'class' => $this->class,
            'hidden' => $this->hidden,
            'docs' => $this->docBlock->toArray(),
            ...$this->payload,
        ];
    }
}

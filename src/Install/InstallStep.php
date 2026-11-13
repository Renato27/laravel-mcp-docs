<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install;

class InstallStep
{
    /**
     * Creates one walkthrough step; its screenshot is supplied by the application through `mcp-docs.screenshots`.
     *
     * @param  string  $key
     * @param  string  $title
     * @param  string|null  $body
     * @param  string|null  $image
     * @param  string|null  $caption
     * @return void
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly ?string $body = null,
        public readonly ?string $image = null,
        public readonly ?string $caption = null,
    ) {
    }

    /**
     * Returns a copy of the step with the given image, keeping the current caption when none is given.
     *
     * @param  string|null  $image
     * @param  string|null  $caption
     * @return self
     */
    public function withImage(?string $image, ?string $caption = null): self
    {
        return new self($this->key, $this->title, $this->body, $image, $caption ?? $this->caption);
    }
}

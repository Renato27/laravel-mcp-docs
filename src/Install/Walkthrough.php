<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install;

use Illuminate\Support\Arr;

class Walkthrough
{
    /**
     * Creates one client's instructions for the operating systems they are identical on.
     *
     * @param  list<OperatingSystem>  $platforms
     * @param  Snippet|null  $snippet
     * @param  list<InstallStep>  $steps
     * @param  string|null  $unavailable  Why this client cannot reach this server here, when it cannot.
     * @param  bool  $configuredSteps  Whether the steps come from the application's configuration rather than the client.
     * @return void
     */
    public function __construct(
        public readonly array $platforms,
        public readonly ?Snippet $snippet = null,
        public readonly array $steps = [],
        public readonly ?string $unavailable = null,
        public readonly bool $configuredSteps = false,
    ) {
    }

    /**
     * The label for this walkthrough's tab: "macOS", or "macOS & Linux".
     *
     * @return string
     */
    public function label(): string
    {
        return Arr::join(array_map(fn (OperatingSystem $os): string => $os->label(), $this->platforms), ', ', ' & ');
    }

    /**
     * The platform values, for the page's OS selector to match against.
     *
     * @return string
     */
    public function value(): string
    {
        return implode(' ', array_map(fn (OperatingSystem $os): string => $os->value, $this->platforms));
    }

    /**
     * Whether this walkthrough applies to the given operating system.
     *
     * @param  OperatingSystem  $os
     * @return bool
     */
    public function covers(OperatingSystem $os): bool
    {
        return in_array($os, $this->platforms, true);
    }

    /**
     * What makes two walkthroughs the same, and so mergeable into one tab.
     *
     * @return string
     */
    public function fingerprint(): string
    {
        return md5(serialize([$this->snippet, $this->steps, $this->unavailable, $this->configuredSteps]));
    }

    /**
     * Returns a copy of the walkthrough covering the given platforms.
     *
     * @param  list<OperatingSystem>  $platforms
     * @return self
     */
    public function withPlatforms(array $platforms): self
    {
        return new self($platforms, $this->snippet, $this->steps, $this->unavailable, $this->configuredSteps);
    }
}

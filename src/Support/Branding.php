<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\HtmlString;

class Branding
{
    /**
     * Creates the branding reader over the application configuration.
     *
     * @param  Config  $config
     * @return void
     */
    public function __construct(protected Config $config) {}

    /**
     * The brand name, falling back to the configured title and then the application name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->get('name') ?? trim(((string) $this->config->get('app.name', 'Laravel')).' MCP');
    }

    /**
     * The tagline shown under the brand name, if configured.
     *
     * @return string|null
     */
    public function tagline(): ?string
    {
        return $this->get('tagline');
    }

    /**
     * The footer text, if configured.
     *
     * @return string|null
     */
    public function footer(): ?string
    {
        return $this->get('footer');
    }

    /**
     * The favicon URL, if configured.
     *
     * @return string|null
     */
    public function favicon(): ?string
    {
        $favicon = $this->get('favicon');

        return $favicon === null ? null : $this->url($favicon);
    }

    /**
     * Whether a separate logo is configured for dark backgrounds.
     *
     * @return bool
     */
    public function hasDarkLogo(): bool
    {
        return $this->get('logo_dark') !== null;
    }

    /**
     * The logo for a given theme, ready to drop into the page.
     *
     * An SVG on disk is inlined, a raster image on disk is embedded as a data URI,
     * and anything else becomes an <img> pointing at its URL.
     *
     * @param  'light'|'dark'  $theme
     * @return HtmlString|null
     */
    public function logo(string $theme = 'light'): ?HtmlString
    {
        $logo = $theme === 'dark' ? ($this->get('logo_dark') ?? $this->get('logo')) : $this->get('logo');

        if ($logo === null) {
            return null;
        }

        if (str_ends_with(strtolower($logo), '.svg') && is_file($logo)) {
            $svg = (string) file_get_contents($logo);

            return new HtmlString(str_contains($svg, '<svg') ? $svg : '');
        }

        $src = htmlspecialchars($this->embedded($logo) ?? $this->url($logo), ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($this->name(), ENT_QUOTES, 'UTF-8');

        return new HtmlString('<img class="brand-logo" src="'.$src.'" alt="'.$alt.'">');
    }

    /**
     * Links shown under the brand, as label => url.
     *
     * @return array<string, string>
     */
    public function links(): array
    {
        $links = $this->config->get('mcp-docs.branding.links', []);

        if (! is_array($links)) {
            return [];
        }

        return array_filter(
            $links,
            fn (mixed $url, mixed $label): bool => is_string($url) && is_string($label),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Palette overrides, emitted as custom properties after the stylesheet.
     *
     * @return HtmlString
     */
    public function paletteCss(): HtmlString
    {
        $accent = $this->get('accent');
        $accentDark = $this->get('accent_dark') ?? $accent;

        $rules = [];

        if ($accent !== null) {
            $rules[] = ':root{--accent:'.$this->color($accent).'}';
        }

        if ($accentDark !== null) {
            $dark = $this->color($accentDark);
            $rules[] = '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){--accent:'.$dark.'}}';
            $rules[] = ':root[data-theme="dark"]{--accent:'.$dark.'}';
        }

        return new HtmlString(implode('', $rules));
    }

    /**
     * Reads a non-empty string branding setting, or null.
     *
     * @param  string  $key
     * @return string|null
     */
    protected function get(string $key): ?string
    {
        $value = $this->config->get("mcp-docs.branding.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Returns the colour when it is safe to inline in a <style> block, otherwise `inherit`.
     *
     * @param  string  $value
     * @return string
     */
    protected function color(string $value): string
    {
        return preg_match('/^(#[0-9a-f]{3,8}|[a-z]+|(rgb|hsl)a?\([\d\s.,%\/]+\))$/i', $value) === 1
            ? $value
            : 'inherit';
    }

    /**
     * A raster image on disk as a data URI; null for anything else.
     *
     * @param  string  $path
     * @return string|null
     */
    protected function embedded(string $path): ?string
    {
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };

        if ($mime === null || ! is_file($path)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    /**
     * Returns absolute, root-relative and data URLs as-is, and resolves anything else through asset().
     *
     * @param  string  $path
     * @return string
     */
    protected function url(string $path): string
    {
        if (preg_match('#^(https?:)?//#i', $path) === 1 || str_starts_with($path, '/') || str_starts_with($path, 'data:')) {
            return $path;
        }

        return asset($path);
    }
}

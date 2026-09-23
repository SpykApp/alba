<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Themes;

use RuntimeException;

/**
 * Loads a theme from a folder, so a theme can be sold/shipped as plain files:
 *
 *   my-theme/theme.json   {"name": "...", "tokens": {"shared": {}, "light": {}, "dark": {}}, "logo": "..."}
 *   my-theme/theme.css    optional
 *   my-theme/views/       optional template overrides
 *   my-theme/assets/      optional images/fonts
 */
final class DirectoryTheme extends BaseTheme
{
    /** @var array<string, mixed> */
    private array $manifest;

    public function __construct(private string $dir)
    {
        $this->dir = rtrim($dir, '/\\');
        $file = $this->dir.'/theme.json';
        $this->manifest = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: [])
            : throw new RuntimeException("Theme manifest not found: $file");
    }

    public static function from(string $dir): self
    {
        return new self($dir);
    }

    public function name(): string
    {
        return (string) ($this->manifest['name'] ?? basename($this->dir));
    }

    public function tokens(): array
    {
        return $this->manifest['tokens'] ?? [];
    }

    public function cssFile(): ?string
    {
        return is_file($this->dir.'/theme.css') ? $this->dir.'/theme.css' : null;
    }

    public function viewsPath(): ?string
    {
        return is_dir($this->dir.'/views') ? $this->dir.'/views' : null;
    }

    public function assetsPath(): ?string
    {
        return is_dir($this->dir.'/assets') ? $this->dir.'/assets' : null;
    }

    public function logo(): string|array|null
    {
        return $this->manifest['logo'] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Themes;

/**
 * A theme pack. Everything is optional: a theme can be just a few colour
 * tokens, or tokens + a stylesheet + template overrides + assets.
 */
interface Theme
{
    public function name(): string;

    /**
     * CSS variable values, without the "--alba-" prefix, e.g. ['bg' => '#fff'].
     * Groups: shared (both modes), light, dark. See Tokens::NAMES.
     *
     * @return array{shared?: array<string, string>, light?: array<string, string>, dark?: array<string, string>}
     */
    public function tokens(): array;

    /** Absolute path to extra CSS, served after the tokens. */
    public function cssFile(): ?string;

    /** Directory of template overrides (same file names as resources/views). */
    public function viewsPath(): ?string;

    /** Directory of images/fonts, served at {route}/_alba/theme/{file}. */
    public function assetsPath(): ?string;

    /** @return string|array{light: string, dark: string}|null */
    public function logo(): string|array|null;
}
